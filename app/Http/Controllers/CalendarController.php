<?php

namespace App\Http\Controllers;

use App\Models\Hearing;
use App\Models\CaseTimeline;
use App\Models\Task;
use App\Models\CaseTeamMember;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CalendarController extends BaseController
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }
    public function index(Request $request)
    {
        $currentDate = $request->get('date', now()->format('Y-m-d'));
        $viewType = $request->get('view', 'month');

        $date = Carbon::parse($currentDate);

        // Calculate date range based on view type
        switch ($viewType) {
            case 'week':
                $startDate = $date->copy()->startOfWeek();
                $endDate = $date->copy()->endOfWeek();
                break;
            case 'day':
                $startDate = $date->copy()->startOfDay();
                $endDate = $date->copy()->endOfDay();
                break;
            default: // month
                $startDate = $date->copy()->startOfMonth()->startOfWeek();
                $endDate = $date->copy()->endOfMonth()->endOfWeek();
                break;
        }

        // Check demo mode
        $isDemo = config('app.is_demo', false);

        if ($isDemo) {
            // Return demo events only
            $allDemoEvents = getDemoCalendarEvents();

            // Filter demo events by date range
            $events = collect($allDemoEvents)->filter(function ($event) use ($startDate, $endDate) {
                $eventDate = Carbon::parse($event['date']);
                return $eventDate->between($startDate, $endDate);
            })->values();

            $upcomingEvents = collect($allDemoEvents)->filter(function ($event) {
                $eventDate = Carbon::parse($event['date']);
                return $eventDate->between(now()->startOfDay(), now()->addDays(7)->endOfDay());
            })->sortBy('date')->take(10)->values();
        } else {
            // Return real database events
            $events = $this->getRealCalendarEvents($startDate, $endDate);
            $upcomingEvents = $this->getUpcomingEvents();
        }

        return Inertia::render('calendar/index', [
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
            'currentDate' => $currentDate,
            'viewType' => $viewType,
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'systemSettings' => settings(),
            'googleCalendarAuthorized' => $this->googleCalendarService->isAuthorized(createdBy()),
            'googleCalendarEnabled' => getSetting('googleCalendarEnabled', '0') === '1',
            'isDemo' => $isDemo
        ]);
    }

    private function getRealCalendarEvents($startDate, $endDate)
    {
        // Get hearings for the date range
        $hearingsQuery = Hearing::withPermissionCheck()
            ->with(['case', 'court', 'judge', 'hearingType'])
            ->whereBetween('hearing_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Filter for team members
        if (auth()->user()->type === 'team_member') {
            $hearingsQuery->whereHas('case.teamMembers', function($query) {
                $query->where('user_id', auth()->id())->where('status', 'active');
            });
        }

        $hearings = $hearingsQuery->get()
            ->map(function ($hearing) {
                return [
                    'id' => 'hearing_' . $hearing->id,
                    'title' => $hearing->title,
                    'type' => 'hearing',
                    'date' => $hearing->hearing_date,
                    'time' => $hearing->hearing_time,
                    'duration' => $hearing->duration_minutes,
                    'status' => $hearing->status,
                    'case_title' => $hearing->case->title ?? '',
                    'court_name' => $hearing->court->name ?? '',
                    'judge_name' => $hearing->judge->name ?? '',
                    'color' => $this->getStatusColor($hearing->status),
                    'google_synced' => !empty($hearing->google_calendar_event_id),
                    'details' => [
                        'hearing_id' => $hearing->hearing_id,
                        'description' => $hearing->description,
                        'notes' => $hearing->notes,
                        'outcome' => $hearing->outcome
                    ]
                ];
            });

        // Get case timelines for the date range
        $timelinesQuery = CaseTimeline::withPermissionCheck()
            ->with(['case', 'eventType'])
            ->whereBetween('event_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Filter for team members
        if (auth()->user()->type === 'team_member') {
            $timelinesQuery->whereHas('case.teamMembers', function($query) {
                $query->where('user_id', auth()->id())->where('status', 'active');
            });
        }

        $timelines = $timelinesQuery->get()
            ->map(function ($timeline) {
                $eventDateTime = \Carbon\Carbon::parse($timeline->event_date);
                return [
                    'id' => 'timeline_' . $timeline->id,
                    'title' => $timeline->title,
                    'type' => 'timeline',
                    'date' => $eventDateTime->format('Y-m-d'),
                    'time' => $eventDateTime->format('H:i'),
                    'status' => $timeline->status,
                    'case_title' => $timeline->case->title ?? '',
                    'event_type' => $timeline->eventType->name ?? $timeline->event_type,
                    'is_completed' => $timeline->is_completed,
                    'color' => $timeline->is_completed ? '#10b77f' : '#f59e0b',
                    'details' => [
                        'description' => $timeline->description,
                        'location' => $timeline->location,
                        'participants' => $timeline->participants
                    ]
                ];
            });

        // Get tasks for the date range
        $tasksQuery = Task::withPermissionCheck()
            ->with(['case', 'assignedUser'])
            ->whereBetween('due_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('due_date');

        // Filter for team members - only show tasks assigned to them or cases they're assigned to
        if (auth()->user()->type === 'team_member') {
            $tasksQuery->where(function($query) {
                $query->where('assigned_to', auth()->id())
                      ->orWhereHas('case.teamMembers', function($q) {
                          $q->where('user_id', auth()->id())->where('status', 'active');
                      });
            });
        }

        $tasks = $tasksQuery->get()
            ->map(function ($task) {
                return [
                    'id' => 'task_' . $task->id,
                    'title' => $task->title,
                    'type' => 'task',
                    'date' => $task->due_date->format('Y-m-d'),
                    'time' => $task->due_date->format('H:i'),
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'case_title' => $task->case->title ?? 'No Case',
                    'assigned_to' => $task->assignedUser->name ?? 'Unassigned',
                    'color' => $this->getTaskColor($task->priority, $task->status),
                    'google_synced' => !empty($task->google_calendar_event_id),
                    'details' => [
                        'task_id' => $task->task_id,
                        'description' => $task->description,
                        'notes' => $task->notes,
                        'estimated_duration' => $task->estimated_duration
                    ]
                ];
            });

        // Get team member assignments for the date range
        $teamMembersQuery = CaseTeamMember::withPermissionCheck()
            ->with(['case', 'user'])
            ->whereBetween('assigned_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Filter for team members - only show their own assignments
        if (auth()->user()->type === 'team_member') {
            $teamMembersQuery->where('user_id', auth()->id());
        }

        $teamMembers = $teamMembersQuery->get()
            ->map(function ($teamMember) {
                return [
                    'id' => 'team_member_' . $teamMember->id,
                    'title' => 'Team Assignment: ' . ($teamMember->user->name ?? 'Unknown'),
                    'type' => 'team_member',
                    'date' => $teamMember->assigned_date,
                    'time' => '09:00', // Default time for team assignments
                    'status' => $teamMember->status,
                    'case_title' => $teamMember->case->title ?? '',
                    'assigned_user' => $teamMember->user->name ?? 'Unknown',
                    'color' => $teamMember->status === 'active' ? '#8b5cf6' : '#6b7280',
                    'google_synced' => !empty($teamMember->google_calendar_event_id),
                    'details' => [
                        'user_name' => $teamMember->user->name ?? 'Unknown',
                        'user_email' => $teamMember->user->email ?? '',
                        'assigned_date' => $teamMember->assigned_date,
                        'role' => $teamMember->role ?? 'Team Member'
                    ]
                ];
            });

        // Get cases for the date range
        $casesQuery = \App\Models\CaseModel::withPermissionCheck()
            ->with(['client', 'caseType', 'caseStatus'])
            ->whereBetween('filing_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('filing_date');

        // Filter for team members
        if (auth()->user()->type === 'team_member') {
            $casesQuery->whereHas('teamMembers', function($query) {
                $query->where('user_id', auth()->id())->where('status', 'active');
            });
        }

        $cases = $casesQuery->get()
            ->map(function ($case) {
                return [
                    'id' => 'case_' . $case->id,
                    'title' => 'Case Filed: ' . $case->title,
                    'type' => 'case',
                    'date' => $case->filing_date,
                    'time' => '09:00', // Default time for case filing
                    'status' => $case->caseStatus->name ?? $case->status,
                    'case_title' => $case->title,
                    'client_name' => $case->client->name ?? 'No Client',
                    'color' => '#6366f1', // Indigo for cases
                    'details' => [
                        'case_number' => $case->case_number,
                        'description' => $case->description,
                        'case_type' => $case->caseType->name ?? '',
                        'filing_date' => $case->filing_date,
                        'client_details' => [
                            'name' => $case->client->name ?? '',
                            'email' => $case->client->email ?? '',
                            'phone' => $case->client->phone ?? ''
                        ]
                    ]
                ];
            });

        // Combine and sort events
        return $hearings->concat($timelines)->concat($tasks)->concat($teamMembers)->concat($cases)->sortBy('date')->values();
    }

    private function getUpcomingEvents()
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(7)->endOfDay();

        $hearingsQuery = Hearing::withPermissionCheck()
            ->with(['case', 'court'])
            ->whereBetween('hearing_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', '!=', 'cancelled');

        if (auth()->user()->type === 'team_member') {
            $hearingsQuery->whereHas('case.teamMembers', function($query) {
                $query->where('user_id', auth()->id())->where('status', 'active');
            });
        }

        $hearings = $hearingsQuery->orderBy('hearing_date')
            ->orderBy('hearing_time')
            ->take(5)
            ->get()
            ->map(function ($hearing) {
                return [
                    'id' => 'hearing_' . $hearing->id,
                    'title' => $hearing->title,
                    'type' => 'hearing',
                    'date' => $hearing->hearing_date,
                    'time' => $hearing->hearing_time,
                    'case_title' => $hearing->case->title ?? '',
                    'court_name' => $hearing->court->name ?? '',
                    'status' => $hearing->status,
                    'color' => $this->getStatusColor($hearing->status)
                ];
            });

        $timelinesQuery = CaseTimeline::withPermissionCheck()
            ->with(['case'])
            ->whereBetween('event_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('is_completed', false);

        if (auth()->user()->type === 'team_member') {
            $timelinesQuery->whereHas('case.teamMembers', function($query) {
                $query->where('user_id', auth()->id())->where('status', 'active');
            });
        }

        $timelines = $timelinesQuery->orderBy('event_date')
            ->take(5)
            ->get()
            ->map(function ($timeline) {
                $eventDateTime = \Carbon\Carbon::parse($timeline->event_date);
                return [
                    'id' => 'timeline_' . $timeline->id,
                    'title' => $timeline->title,
                    'type' => 'timeline',
                    'date' => $eventDateTime->format('Y-m-d'),
                    'time' => $eventDateTime->format('H:i'),
                    'case_title' => $timeline->case->title ?? '',
                    'status' => $timeline->status,
                    'color' => '#f59e0b'
                ];
            });

        $teamMembersQuery = CaseTeamMember::withPermissionCheck()
            ->with(['case', 'user'])
            ->whereBetween('assigned_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', 'active');

        if (auth()->user()->type === 'team_member') {
            $teamMembersQuery->where('user_id', auth()->id());
        }

        $teamMembers = $teamMembersQuery->orderBy('assigned_date')
            ->take(3)
            ->get()
            ->map(function ($teamMember) {
                return [
                    'id' => 'team_member_' . $teamMember->id,
                    'title' => 'Team Assignment: ' . ($teamMember->user->name ?? 'Unknown'),
                    'type' => 'team_member',
                    'date' => $teamMember->assigned_date,
                    'time' => '09:00',
                    'case_title' => $teamMember->case->title ?? '',
                    'status' => $teamMember->status,
                    'color' => '#8b5cf6'
                ];
            });

        return $hearings->concat($timelines)->concat($teamMembers)->sortBy('date')->take(10)->values();
    }

    private function getStatusColor($status)
    {
        $colors = [
            'scheduled' => '#3b82f6',
            'in_progress' => '#f59e0b',
            'completed' => '#10b77f',
            'postponed' => '#f97316',
            'cancelled' => '#ef4444',
            'pending' => '#6b7280',
            'active' => '#10b77f'
        ];

        return $colors[$status] ?? '#6b7280';
    }

    private function getTaskColor($priority, $status)
    {
        if ($status === 'completed') {
            return '#10b77f'; // Green for completed
        }

        $priorityColors = [
            'critical' => '#dc2626', // Red
            'high' => '#ea580c',     // Orange
            'medium' => '#d97706',   // Amber
            'low' => '#65a30d'       // Lime
        ];

        return $priorityColors[$priority] ?? '#6b7280';
    }
}
