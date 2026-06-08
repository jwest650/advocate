<?php

namespace App\Http\Controllers;

use App\Models\CaseTeamMember;
use App\Models\CaseModel;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CaseTeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseTeamMember::query()
            ->with(['case', 'user', 'creator'])
            ->whereHas('case', function ($q) {
                $q->where('created_by', createdBy());
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('case', function ($caseQuery) use ($request) {
                        $caseQuery->where('title', 'like', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->has('case_id') && !empty($request->case_id) && $request->case_id !== 'all') {
            $query->where('case_id', $request->case_id);
        }



        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('assigned_date', 'desc');
        }

        $teamMembers = $query->paginate($request->per_page ?? 10);
        $cases = CaseModel::where('created_by', createdBy())->where('status', 'active')->get(['id', 'title', 'case_id']);
        $users = User::where('created_by', createdBy())->orWhere('id', createdBy())->where('status', 'active')->get(['id', 'name']);

        return Inertia::render('cases/case-team-members/index', [
            'teamMembers' => $teamMembers,
            'cases' => $cases,
            'users' => $users,
            'filters' => $request->all(['search', 'case_id', 'role', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_id' => 'required|exists:cases,id',
            'user_id' => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'status' => 'nullable|in:active,inactive',
            'sync_with_google_calendar' => 'nullable|boolean',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        $case = CaseModel::where('id', $validated['case_id'])->where('created_by', createdBy())->first();
        if (!$case) {
            return redirect()->back()->with('error', 'Invalid case selected.');
        }

        $user = User::where('id', $validated['user_id'])
            ->where(function ($q) {
                $q->where('created_by', createdBy())->orWhere('id', createdBy());
            })->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Invalid user selected.');
        }

        $exists = CaseTeamMember::where('case_id', $validated['case_id'])
            ->where('user_id', $validated['user_id'])
            ->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'User is already assigned to this case.');
        }

        $teamMember = CaseTeamMember::create($validated);

        // Handle Google Calendar sync
        if ($teamMember && $request->sync_with_google_calendar) {
            $teamMember->load('user');
            $calendarService = new GoogleCalendarService();
            $eventId = $calendarService->createEvent($teamMember, createdBy(), 'team_member');
            if ($eventId) {
                $teamMember->update(['google_calendar_event_id' => $eventId]);
            }
        }

        return redirect()->back()->with('success', 'Team member assigned successfully.');
    }

    public function update(Request $request, $teamMemberId)
    {
        $teamMember = CaseTeamMember::whereHas('case', function ($q) {
            $q->where('created_by', createdBy());
        })->where('id', $teamMemberId)->first();

        if (!$teamMember) {
            return redirect()->back()->with('error', 'Team member assignment not found.');
        }

        $validated = $request->validate([
            'case_id' => 'required|exists:cases,id',
            'user_id' => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'status' => 'nullable|in:active,inactive',
            'sync_with_google_calendar' => 'nullable|boolean',
        ]);

        $case = CaseModel::where('id', $validated['case_id'])->where('created_by', createdBy())->first();
        if (!$case) {
            return redirect()->back()->with('error', 'Invalid case selected.');
        }

        $user = User::where('id', $validated['user_id'])
            ->where(function ($q) {
                $q->where('created_by', createdBy())->orWhere('id', createdBy());
            })->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Invalid user selected.');
        }

        $exists = CaseTeamMember::where('case_id', $validated['case_id'])
            ->where('user_id', $validated['user_id'])
            ->where('id', '!=', $teamMemberId)
            ->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'User is already assigned to this case.');
        }

        $teamMember->update($validated);

        // Handle Google Calendar sync
        if ($request->sync_with_google_calendar && !$teamMember->google_calendar_event_id) {
            $teamMember->load('user');
            $calendarService = new GoogleCalendarService();
            $eventId = $calendarService->createEvent($teamMember, createdBy(), 'team_member');
            if ($eventId) {
                $teamMember->update(['google_calendar_event_id' => $eventId]);
            }
        } elseif ($request->sync_with_google_calendar && $teamMember->google_calendar_event_id) {
            $teamMember->load('user');
            $calendarService = new GoogleCalendarService();
            $calendarService->updateEvent($teamMember->google_calendar_event_id, $teamMember, createdBy(), 'team_member');
        } elseif (!$request->sync_with_google_calendar && $teamMember->google_calendar_event_id) {
            $calendarService = new GoogleCalendarService();
            $calendarService->deleteEvent($teamMember->google_calendar_event_id, createdBy());
            $teamMember->update(['google_calendar_event_id' => null]);
        }

        return redirect()->back()->with('success', 'Team member assignment updated successfully.');
    }

    public function destroy($teamMemberId)
    {
        $teamMember = CaseTeamMember::whereHas('case', function ($q) {
            $q->where('created_by', createdBy());
        })->where('id', $teamMemberId)->first();

        if (!$teamMember) {
            return redirect()->back()->with('error', 'Team member assignment not found.');
        }

        // Delete Google Calendar event if exists
        if ($teamMember->google_calendar_event_id) {
            $calendarService = new GoogleCalendarService();
            $calendarService->deleteEvent($teamMember->google_calendar_event_id, createdBy());
        }

        $teamMember->delete();

        return redirect()->back()->with('success', 'Team member assignment removed successfully.');
    }

    public function toggleStatus($teamMemberId)
    {
        $teamMember = CaseTeamMember::whereHas('case', function ($q) {
            $q->where('created_by', createdBy());
        })->where('id', $teamMemberId)->first();

        if (!$teamMember) {
            return redirect()->back()->with('error', 'Team member assignment not found.');
        }

        $teamMember->status = $teamMember->status === 'active' ? 'inactive' : 'active';
        $teamMember->save();

        return redirect()->back()->with('success', 'Team member status updated successfully.');
    }
}