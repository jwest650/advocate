<?php

namespace App\Services;

use App\Models\Setting;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;

class GoogleCalendarService
{
    private $client;
    private $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->service = new Google_Service_Calendar($this->client);
    }

    public function isEnabled($userId)
    {
        // Check if Google Calendar is globally enabled
        $globalEnabled = getSetting('googleCalendarEnabled', '0', $userId) === '1';
        \Log::info('Google Calendar enabled check', [
            'user_id' => $userId,
            'enabled' => $globalEnabled
        ]);
        return $globalEnabled;
    }

    private function setupClient($userId)
    {
        $settings = Setting::where('user_id', $userId)
            ->whereIn('key', ['googleCalendarJsonPath', 'googleCalendarId'])
            ->pluck('value', 'key');

        $jsonPath = $settings['googleCalendarJsonPath'] ?? null;
        
        if (!$jsonPath) {
            throw new \Exception('Google Calendar JSON credentials not configured');
        }

        // Find the correct path for the JSON file
        $paths = [
            $jsonPath,
            storage_path($jsonPath),
            storage_path('app/' . $jsonPath),
            base_path($jsonPath),
            public_path('storage/' . $jsonPath),
        ];
        
        $validPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $validPath = $path;
                break;
            }
        }
        
        if (!$validPath) {
            throw new \Exception('Google Calendar JSON file not found at: ' . $jsonPath);
        }

        // Use service account authentication
        $this->client->setAuthConfig($validPath);
        $this->client->setScopes(Google_Service_Calendar::CALENDAR);
        $this->client->useApplicationDefaultCredentials();
    }

    public function createEvent($item, $userId, $type = 'task')
    {
        if (!$this->isEnabled($userId)) {
            \Log::info('Google Calendar not enabled for user', ['user_id' => $userId]);
            return null;
        }

        try {
            \Log::info('Setting up Google Calendar client', ['user_id' => $userId, 'type' => $type]);
            $this->setupClient($userId);

            $summary = $item->title ?? ($type === 'team_member' ? 'Team Member Assignment: ' . ($item->user->name ?? 'Unknown') : 'Event');
            $description = $item->description ?? ($type === 'team_member' ? 'Team member assigned to case' : '');
            
            $event = new Google_Service_Calendar_Event([
                'summary' => $summary,
                'description' => $description,
                // Store metadata in private extended properties instead of description
                'extendedProperties' => [
                    'private' => [
                        'app_type' => $type,
                        'app_id' => $item->id,
                        'app_user_id' => $userId
                    ]
                ]
            ]);

            // Set date/time based on item type
            if ($type === 'hearing' && $item->hearing_date) {
                $startTime = \Carbon\Carbon::parse($item->hearing_date);
                if ($item->hearing_time) {
                    // Handle hearing_time which might be a datetime string or just time
                    $timeString = $item->hearing_time;
                    if (strpos($timeString, ' ') !== false) {
                        // Extract time part from datetime string
                        $timeString = explode(' ', $timeString)[1];
                    }
                    $timeParts = explode(':', $timeString);
                    $startTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                }
                $endTime = clone $startTime;
                $endTime->addMinutes($item->duration_minutes ?? 60);
            } elseif ($type === 'case' && $item->filing_date) {
                $startTime = $item->filing_date;
                $endTime = clone $startTime;
                $endTime->addHour();
            } elseif ($type === 'timeline' && $item->event_date) {
                $startTime = $item->event_date;
                $endTime = clone $item->event_date;
                $endTime->addHour();
            } elseif ($type === 'team_member' && $item->assigned_date) {
                $startTime = $item->assigned_date;
                $endTime = clone $item->assigned_date;
                $endTime->addHour();
            } elseif ($type === 'task' && $item->due_date) {
                $startTime = $item->due_date->copy();
                // Set default time to 9:00 AM if no time is specified
                if ($startTime->format('H:i') === '00:00') {
                    $startTime->setTime(9, 0);
                }
                $endTime = $startTime->copy()->addHour();
            } else {
                return null;
            }

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startTime->format('c'));
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endTime->format('c'));
            $event->setEnd($end);

            // Get calendar ID from settings
            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';
                
            $calendarEvent = $this->service->events->insert($calendarId, $event);
            $eventId = $calendarEvent->getId();
            return $eventId;
        } catch (\Exception $e) {
            \Log::error('Google Calendar event creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type,
                'item_id' => $item->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function updateEvent($eventId, $item, $userId, $type = 'task')
    {
        if (!$this->isEnabled($userId) || !$eventId) {
            return false;
        }

        try {
            $this->setupClient($userId);

            // Get calendar ID from settings
            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';
                
            $event = $this->service->events->get($calendarId, $eventId);
            
            $summary = $item->title ?? ($type === 'team_member' ? 'Team Member Assignment: ' . ($item->user->name ?? 'Unknown') : 'Event');
            $description = $item->description ?? ($type === 'team_member' ? 'Team member assigned to case' : '');
            
            $event->setSummary($summary);
            $event->setDescription($description);
            
            // Update extended properties for team members
            if ($type === 'team_member') {
                $event->setExtendedProperties([
                    'private' => [
                        'app_type' => $type,
                        'app_id' => $item->id,
                        'app_user_id' => $userId
                    ]
                ]);
            }

            // Set date/time based on item type
            if ($type === 'hearing' && $item->hearing_date) {
                $startTime = \Carbon\Carbon::parse($item->hearing_date);
                if ($item->hearing_time) {
                    // Handle hearing_time which might be a datetime string or just time
                    $timeString = $item->hearing_time;
                    if (strpos($timeString, ' ') !== false) {
                        // Extract time part from datetime string
                        $timeString = explode(' ', $timeString)[1];
                    }
                    $timeParts = explode(':', $timeString);
                    $startTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                }
                $endTime = clone $startTime;
                $endTime->addMinutes($item->duration_minutes ?? 60);
            } elseif ($type === 'case' && $item->filing_date) {
                $startTime = $item->filing_date;
                $endTime = clone $startTime;
                $endTime->addHour();
            } elseif ($type === 'timeline' && $item->event_date) {
                $startTime = $item->event_date;
                $endTime = clone $item->event_date;
                $endTime->addHour();
            } elseif ($type === 'team_member' && $item->assigned_date) {
                $startTime = $item->assigned_date;
                $endTime = clone $item->assigned_date;
                $endTime->addHour();
            } elseif ($type === 'task' && $item->due_date) {
                $startTime = $item->due_date->copy();
                // Set default time to 9:00 AM if no time is specified
                if ($startTime->format('H:i') === '00:00') {
                    $startTime->setTime(9, 0);
                }
                $endTime = $startTime->copy()->addHour();
            } else {
                return false;
            }

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startTime->format('c'));
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endTime->format('c'));
            $event->setEnd($end);

            $this->service->events->update($calendarId, $eventId, $event);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar event update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($eventId, $userId)
    {
        if (!$this->isEnabled($userId) || !$eventId) {
            return false;
        }

        try {
            $this->setupClient($userId);
            // Get calendar ID from settings
            $calendarId = Setting::where('user_id', $userId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';
                
            $this->service->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar event deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getEvents($userId, $maxResults = 100, $timeMin = null, $timeMax = null)
    {
        // Use createdBy() for settings but keep userId for filtering
        $settingsUserId = createdBy();
        
        if (!$this->isEnabled($settingsUserId)) {
            return [];
        }

        try {
            $this->setupClient($settingsUserId);
            
            // Get calendar ID from settings
            $calendarId = Setting::where('user_id', $settingsUserId)
                ->where('key', 'googleCalendarId')
                ->value('value') ?: 'primary';
            
            $optParams = [
                'maxResults' => $maxResults,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin ?: date('c', strtotime('-1 month')),
            ];
            
            if ($timeMax) {
                $optParams['timeMax'] = $timeMax;
            }
            
            $results = $this->service->events->listEvents($calendarId, $optParams);
            $events = $results->getItems();
            
            // Get current user for filtering
            $currentUser = \App\Models\User::find($userId);
            
            $filteredEvents = array_filter(array_map(function($event) use ($userId, $currentUser, $settingsUserId) {
                $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();
                $title = $event->getSummary() ?: 'Untitled Event';
                $description = $event->getDescription() ?: '';
                
                // Extract metadata from extended properties to get original record info
                $originalData = $this->extractOriginalData($event, $settingsUserId);
                
                \Log::info('Google Calendar event processing', [
                    'event_title' => $title,
                    'user_id' => $userId,
                    'settings_user_id' => $settingsUserId,
                    'has_original_data' => !is_null($originalData)
                ]);
                
                // Filter for team members: only show events for cases they are assigned to
                if ($currentUser && $currentUser->type === 'team_member') {
                    if ($originalData) {
                        $type = $originalData['type'] ?? null;
                        $recordId = $originalData['record_id'] ?? null;
                        
                        // For team_member events, only show if it's their own assignment
                        if ($type === 'team_member') {
                            $teamMember = \App\Models\CaseTeamMember::find($recordId);
                            if (!$teamMember || $teamMember->user_id != $userId) {
                                return null;
                            }
                        } else {
                            // For other event types, check case assignment
                            $caseId = $this->getCaseIdFromEvent($originalData);
                            if (!$caseId || !$this->isUserAssignedToCase($userId, $caseId)) {
                                return null;
                            }
                        }
                    } else {
                        return null; // Hide non-case events for team members
                    }
                }
                
                if ($originalData) {
                    // Use data from original database record
                    $type = $originalData['type'];
                    $color = $originalData['color'];
                    $caseTitle = $originalData['case_title'];
                    $details = $originalData['details'];
                } else {
                    // Fallback to keyword detection for external Google Calendar events
                    $type = 'google';
                    $color = '#4285f4';
                    $caseTitle = 'Google Calendar';
                    
                    $titleLower = strtolower($title);
                    $descLower = strtolower($description);
                    
                    if (strpos($titleLower, 'hearing') !== false || strpos($descLower, 'hearing') !== false) {
                        $type = 'hearing';
                        $color = '#3b82f6';
                        $caseTitle = 'Court Hearing';
                    } elseif (strpos($titleLower, 'task') !== false || strpos($descLower, 'task') !== false) {
                        $type = 'task';
                        $color = '#f59e0b';
                        $caseTitle = 'Task';
                    } elseif (strpos($titleLower, 'meeting') !== false || strpos($descLower, 'meeting') !== false) {
                        $type = 'timeline';
                        $color = '#10b77f';
                        $caseTitle = 'Meeting';
                    } elseif (strpos($titleLower, 'team') !== false || strpos($descLower, 'team') !== false) {
                        $type = 'team_member';
                        $color = '#8b5cf6';
                        $caseTitle = 'Team Event';
                    }
                    
                    $details = [
                        'description' => $description,
                        'location' => $event->getLocation() ?: '',
                        'calendar_source' => 'Google Calendar'
                    ];
                }
                
                return [
                    'id' => 'google_' . $event->getId(),
                    'title' => $title,
                    'description' => $originalData ? $originalData['clean_description'] : $description,
                    'date' => substr($start, 0, 10),
                    'time' => strpos($start, 'T') !== false ? substr($start, 11, 8) : null,
                    'end_time' => strpos($end, 'T') !== false ? substr($end, 11, 8) : null,
                    'duration' => $this->calculateDuration($start, $end),
                    'type' => $type,
                    'color' => $color,
                    'case_title' => $caseTitle,
                    'court_name' => $originalData['court_name'] ?? null,
                    'judge_name' => $originalData['judge_name'] ?? null,
                    'assigned_to' => $originalData['assigned_to'] ?? null,
                    'client_name' => $originalData['client_name'] ?? null,
                    'priority' => $originalData['priority'] ?? null,
                    'location' => $originalData['location'] ?? $event->getLocation(),
                    'status' => $originalData['status'] ?? 'active',
                    'source' => 'google',
                    'details' => $details
                ];
            }, $events));
            
            return array_values($filteredEvents);
        } catch (\Exception $e) {
            \Log::error('Google Calendar events fetch failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function isAuthorized($userId)
    {
        $jsonPath = Setting::where('user_id', $userId)
            ->where('key', 'googleCalendarJsonPath')
            ->first();
        
        if (!$jsonPath || empty($jsonPath->value)) {
            return false;
        }
        
        // Try different path combinations
        $paths = [
            $jsonPath->value, // Original path
            storage_path($jsonPath->value), // Storage path
            storage_path('app/' . $jsonPath->value), // Storage app path
            base_path($jsonPath->value), // Base path
            public_path('storage/' . $jsonPath->value), // Public storage path
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        
        return false;
    }

    private function extractOriginalData($event, $userId)
    {
        // First try extended properties (new method)
        $extendedProps = $event->getExtendedProperties();
        if ($extendedProps && $extendedProps->getPrivate()) {
            $privateProps = $extendedProps->getPrivate();
            $type = $privateProps['app_type'] ?? null;
            $recordId = $privateProps['app_id'] ?? null;
            $originalUserId = $privateProps['app_user_id'] ?? null;
            
            if ($type && $recordId) {
                $description = $event->getDescription() ?: '';
                
                try {
                    $data = null;
                    switch ($type) {
                        case 'hearing':
                            $data = $this->getHearingData($recordId, $description);
                            break;
                        case 'task':
                            $data = $this->getTaskData($recordId, $description);
                            break;
                        case 'timeline':
                            $data = $this->getTimelineData($recordId, $description);
                            break;
                        case 'case':
                            $data = $this->getCaseData($recordId, $description);
                            break;
                        case 'team_member':
                            $data = $this->getTeamMemberData($recordId, $description);
                            break;
                    }
                    
                    if ($data) {
                        $data['type'] = $type;
                        $data['record_id'] = $recordId;
                    }
                    
                    return $data;
                } catch (\Exception $e) {
                    \Log::error('Failed to fetch original data: ' . $e->getMessage());
                }
            }
        }
        
        // Fallback to description parsing for existing events
        $description = $event->getDescription() ?: '';
        if (preg_match('/\[METADATA: type=([^,]+), id=([^,]+), user_id=([^\]]+)\]/', $description, $matches)) {
            $type = $matches[1];
            $recordId = $matches[2];
            $originalUserId = $matches[3];
            
            // Clean description by removing metadata
            $cleanDescription = preg_replace('/\n\n\[METADATA:.*?\]/', '', $description);
            
            try {
                $data = null;
                switch ($type) {
                    case 'hearing':
                        $data = $this->getHearingData($recordId, $cleanDescription);
                        break;
                    case 'task':
                        $data = $this->getTaskData($recordId, $cleanDescription);
                        break;
                    case 'timeline':
                        $data = $this->getTimelineData($recordId, $cleanDescription);
                        break;
                    case 'case':
                        $data = $this->getCaseData($recordId, $cleanDescription);
                        break;
                    case 'team_member':
                        $data = $this->getTeamMemberData($recordId, $cleanDescription);
                        break;
                }
                
                if ($data) {
                    $data['type'] = $type;
                    $data['record_id'] = $recordId;
                }
                
                return $data;
            } catch (\Exception $e) {
                \Log::error('Failed to fetch original data: ' . $e->getMessage());
                
            }
        }
        
        return null;
    }

    private function getCaseIdFromEvent($originalData)
    {
        $type = $originalData['type'] ?? null;
        $recordId = $originalData['record_id'] ?? null;
        
        if (!$recordId) return null;
        
        switch ($type) {
            case 'case':
                return $recordId;
            case 'task':
                $task = \App\Models\Task::find($recordId);
                return $task ? $task->case_id : null;
            case 'hearing':
                $hearing = \App\Models\Hearing::find($recordId);
                return $hearing ? $hearing->case_id : null;
            case 'timeline':
                $timeline = \App\Models\CaseTimeline::find($recordId);
                return $timeline ? $timeline->case_id : null;
            case 'team_member':
                $teamMember = \App\Models\CaseTeamMember::find($recordId);
                return $teamMember ? $teamMember->case_id : null;
        }
        
        return null;
    }

    private function isUserAssignedToCase($userId, $caseId)
    {
        return \App\Models\CaseTeamMember::where('case_id', $caseId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }
    
    private function getHearingData($id, $cleanDescription)
    {
        $hearing = \App\Models\Hearing::with(['case.client', 'court', 'judge'])->find($id);
        if (!$hearing) return null;
        
        return [
            'type' => 'hearing',
            'color' => '#3b82f6',
            'case_title' => $hearing->case->title ?? 'No Case',
            'client_name' => $hearing->case->client->name ?? 'No Client',
            'court_name' => $hearing->court->name ?? '',
            'judge_name' => $hearing->judge->name ?? '',
            'location' => $hearing->court->address ?? '',
            'status' => $hearing->status,
            'clean_description' => $cleanDescription,
            'details' => [
                'hearing_id' => $hearing->hearing_id,
                'description' => $cleanDescription,
                'notes' => $hearing->notes,
                'outcome' => $hearing->outcome,
                'status' => $hearing->status,
                'case_number' => $hearing->case->case_number ?? '',
                'client_details' => [
                    'name' => $hearing->case->client->name ?? '',
                    'email' => $hearing->case->client->email ?? '',
                    'phone' => $hearing->case->client->phone ?? ''
                ]
            ]
        ];
    }
    
    private function getTaskData($id, $cleanDescription)
    {
        $task = \App\Models\Task::with(['case.client', 'assignedUser'])->find($id);
        if (!$task) return null;
        
        return [
            'type' => 'task',
            'color' => $this->getTaskColor($task->priority, $task->status),
            'case_title' => $task->case->title ?? 'No Case',
            'client_name' => $task->case->client->name ?? 'No Client',
            'assigned_to' => $task->assignedUser->name ?? 'Unassigned',
            'priority' => $task->priority,
            'status' => $task->status,
            'clean_description' => $cleanDescription,
            'details' => [
                'task_id' => $task->task_id,
                'description' => $cleanDescription,
                'notes' => $task->notes,
                'status' => $task->status,
                'priority' => $task->priority,
                'estimated_duration' => $task->estimated_duration,
                'case_number' => $task->case->case_number ?? '',
                'client_details' => [
                    'name' => $task->case->client->name ?? '',
                    'email' => $task->case->client->email ?? '',
                    'phone' => $task->case->client->phone ?? ''
                ]
            ]
        ];
    }
    
    private function getTimelineData($id, $cleanDescription)
    {
        $timeline = \App\Models\CaseTimeline::with(['case.client', 'eventType'])->find($id);
        if (!$timeline) return null;
        
        return [
            'type' => 'timeline',
            'color' => $timeline->is_completed ? '#10b77f' : '#f59e0b',
            'case_title' => $timeline->case->title ?? 'No Case',
            'client_name' => $timeline->case->client->name ?? 'No Client',
            'location' => $timeline->location ?? '',
            'status' => $timeline->status ?? 'active',
            'clean_description' => $cleanDescription,
            'details' => [
                'description' => $cleanDescription,
                'location' => $timeline->location,
                'participants' => $timeline->participants,
                'status' => $timeline->status,
                'event_type' => $timeline->eventType->name ?? $timeline->event_type,
                'is_completed' => $timeline->is_completed,
                'case_number' => $timeline->case->case_number ?? '',
                'client_details' => [
                    'name' => $timeline->case->client->name ?? '',
                    'email' => $timeline->case->client->email ?? '',
                    'phone' => $timeline->case->client->phone ?? ''
                ]
            ]
        ];
    }
    
    private function getCaseData($id, $cleanDescription)
    {
        $case = \App\Models\CaseModel::with(['client', 'caseType', 'caseStatus'])->find($id);
        if (!$case) return null;
        
        return [
            'type' => 'case',
            'color' => '#6366f1',
            'case_title' => $case->title,
            'client_name' => $case->client->name ?? 'No Client',
            'status' => $case->caseStatus->name ?? $case->status,
            'clean_description' => $cleanDescription,
            'details' => [
                'case_number' => $case->case_number,
                'description' => $cleanDescription,
                'status' => $case->caseStatus->name ?? $case->status,
                'case_type' => $case->caseType->name ?? '',
                'filing_date' => $case->filing_date,
                'client_details' => [
                    'name' => $case->client->name ?? '',
                    'email' => $case->client->email ?? '',
                    'phone' => $case->client->phone ?? '',
                    'address' => $case->client->address ?? ''
                ]
            ]
        ];
    }
    
    private function getTeamMemberData($id, $cleanDescription)
    {
        $teamMember = \App\Models\CaseTeamMember::with(['case.client', 'user'])->find($id);
        if (!$teamMember) return null;
        
        return [
            'type' => 'team_member',
            'color' => '#8b5cf6',
            'case_title' => $teamMember->case->title ?? 'No Case',
            'client_name' => $teamMember->case->client->name ?? 'No Client',
            'assigned_to' => $teamMember->user->name ?? 'Unknown',
            'status' => $teamMember->status,
            'clean_description' => $cleanDescription,
            'details' => [
                'user_name' => $teamMember->user->name ?? 'Unknown',
                'user_email' => $teamMember->user->email ?? '',
                'assigned_date' => $teamMember->assigned_date,
                'role' => $teamMember->role ?? 'Team Member',
                'status' => $teamMember->status,
                'case_number' => $teamMember->case->case_number ?? '',
                'client_details' => [
                    'name' => $teamMember->case->client->name ?? '',
                    'email' => $teamMember->case->client->email ?? '',
                    'phone' => $teamMember->case->client->phone ?? ''
                ]
            ]
        ];
    }
    
    private function getTaskColor($priority, $status)
    {
        if ($status === 'completed') {
            return '#10b77f';
        }
        
        $priorityColors = [
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#d97706',
            'low' => '#65a30d'
        ];
        
        return $priorityColors[$priority] ?? '#6b7280';
    }

    private function calculateDuration($start, $end)
    {
        if (!$start || !$end) return null;
        
        try {
            $startTime = new \DateTime($start);
            $endTime = new \DateTime($end);
            $diff = $startTime->diff($endTime);
            
            $minutes = ($diff->h * 60) + $diff->i;
            return $minutes > 0 ? $minutes : 60; // Default to 60 minutes if no duration
        } catch (\Exception $e) {
            return 60; // Default duration
        }
    }



    public function getAuthorizationUrl($userId)
    {
        try {
            $settings = Setting::where('user_id', $userId)
                ->whereIn('key', ['googleCalendarClientId', 'googleCalendarSecret', 'googleCalendarRedirectUri'])
                ->pluck('value', 'key');

            // Fallback to environment variables or config
            $clientId = $settings['googleCalendarClientId'] ?? config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID');
            $clientSecret = $settings['googleCalendarSecret'] ?? config('services.google.client_secret') ?? env('GOOGLE_CLIENT_SECRET');

            if (!$clientId || !$clientSecret) {
                throw new \Exception('Google Calendar credentials not configured');
            }

            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->client->setRedirectUri($settings['googleCalendarRedirectUri'] ?? route('google-calendar.callback'));
            $this->client->setScopes(Google_Service_Calendar::CALENDAR);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');

            return $this->client->createAuthUrl();
        } catch (\Exception $e) {
            \Log::error('Failed to generate Google Calendar authorization URL: ' . $e->getMessage());
            return null;
        }
    }
}