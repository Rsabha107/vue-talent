<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Ems\Event;
use Illuminate\Http\Request;

/**
 * Base controller for all Meridian HR controllers
 * Provides event-aware helper methods and shared context
 */
abstract class BaseHRController extends Controller
{
    /**
     * Get the currently selected event ID from session
     */
    protected function getSelectedEventId(): ?int
    {
        return session('selected_event_id');
    }

    /**
     * Get the currently selected event model
     */
    protected function getSelectedEvent(): ?Event
    {
        $eventId = $this->getSelectedEventId();
        return $eventId ? Event::find($eventId) : null;
    }

    /**
     * Check if an event is currently selected
     */
    protected function hasEventSelected(): bool
    {
        return !is_null($this->getSelectedEventId());
    }

    /**
     * Require event selection - redirect if not selected
     * 
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function requireEventSelection()
    {
        if (!$this->hasEventSelected()) {
            return redirect()->route('hr.dashboard')
                ->with('error', 'Please select an event first');
        }
        
        return null;
    }

    /**
     * Get employees query scoped to current event
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getEventEmployees()
    {
        $eventId = $this->getSelectedEventId();
        
        if (!$eventId) {
            return Employee::query();
        }
        
        return Employee::whereHas('events', function ($q) use ($eventId) {
            $q->where('events.id', $eventId)
              ->where('employee_events.is_active', 1);
        });
    }

    /**
     * Get all available events for the current user
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailableEvents()
    {
        return Event::where('active_flag', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'event_logo']);
    }

    /**
     * Share event context for Inertia pages
     * Call this in your controller methods to add event data to page props
     * 
     * @return array
     */
    protected function shareEventContext(): array
    {
        $eventId = $this->getSelectedEventId();
        $event = $eventId ? Event::find($eventId) : null;
        
        return [
            'selectedEvent' => $eventId,
            'eventContext' => $event ? [
                'id' => $event->id,
                'name' => $event->name,
                'logo' => $event->event_logo,
            ] : null,
            'availableEvents' => $this->getAvailableEvents(),
        ];
    }

    /**
     * Get HR role from authenticated user's Spatie roles
     * Returns granular role for permission-based access control
     * Priority: admin > manager > employee-full > employee-basic
     */
    protected function getHRRole(): string
    {
        $user = auth()->user();
        
        if (!$user) {
            return 'employee-basic';
        }
        
        // Check Spatie roles in priority order
        if ($user->hasRole(['admin', 'administrator', 'hr-admin'])) {
            return 'admin';
        }
        
        if ($user->hasRole(['manager', 'supervisor'])) {
            return 'manager';
        }
        
        // Check for employee variants
        if ($user->hasRole('employee-full')) {
            return 'employee-full';
        }
        
        if ($user->hasRole('employee-basic')) {
            return 'employee-basic';
        }
        
        // Check legacy 'employee' role - treat as employee-full
        if ($user->hasRole('employee')) {
            return 'employee-full';
        }
        
        // Default to basic employee for any authenticated user
        return 'employee-basic';
    }
    
    /**
     * Check if user has extended employee access
     * Returns true for employee-full, manager, or admin
     */
    protected function hasExtendedAccess(): bool
    {
        $role = $this->getHRRole();
        return in_array($role, ['employee-full', 'manager', 'admin']);
    }
    
    /**
     * Check if user is admin or manager
     */
    protected function isManagerOrAdmin(): bool
    {
        $role = $this->getHRRole();
        return in_array($role, ['manager', 'admin']);
    }

    /**
     * Mock user data - replace with actual auth user in production
     */
    protected function me(): array
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->first();
        
        if ($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'email' => $employee->work_email_address ?? $employee->personal_email_address,
                'role' => $employee->designation?->name ?? 'Employee',
                'department' => $employee->department?->name,
                'empNumber' => $employee->employee_number,
                'avatarColor' => $employee->avatarColor,
                'initials' => $employee->initials,
            ];
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'Employee',
            'department' => null,
            'empNumber' => null,
            'avatarColor' => $user->id % 7,
            'initials' => strtoupper(substr($user->name, 0, 2)),
        ];
    }

    /**
     * Get common props that all HR pages need
     * 
     * @param string $page - The current page identifier (e.g., 'dashboard', 'leave', 'employee')
     * @return array
     */
    /**
     * Get pending approval counts for navigation badges
     */
    protected function getPendingCounts(): array
    {
        $eventId = $this->getSelectedEventId();
        
        // Get pending leave requests count for selected event
        $pendingLeaves = \App\Models\EmployeeLeaveRequest::forEvent($eventId)
            ->where('status_id', \App\Models\EmployeeLeaveStatus::pendingId())
            ->active()
            ->count();
        
        // Get pending timesheets count for selected event
        $pendingTimesheets = \App\Models\EmployeeTimesheet::forEvent($eventId)
            ->where('status_id', \App\Models\EmployeeTimesheetStatus::submittedId())
            ->active()
            ->count();
        
        return [
            'pendingLeaves' => $pendingLeaves,
            'pendingTimesheets' => $pendingTimesheets,
        ];
    }

    protected function getCommonProps(string $page): array
    {
        return [
            'hrRole' => $this->getHRRole(),
            'hrPage' => $page,
            'me' => $this->me(),
            'pendingCounts' => $this->getPendingCounts(),
            ...$this->shareEventContext(),
        ];
    }
}
