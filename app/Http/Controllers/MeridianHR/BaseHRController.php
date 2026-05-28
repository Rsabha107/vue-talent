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
     * Get effective event IDs for filtering based on role and selection
     * 
     * @return int|array|null
     *   - int: specific event selected
     *   - array: manager's assigned events (when no event selected)
     *   - null: admin with no event selected (all events)
     */
    protected function getEffectiveEventIds()
    {
        $eventId = $this->getSelectedEventId();
        $hrRole = $this->getHRRole();
        
        // If specific event selected, return that
        if ($eventId) {
            return $eventId;
        }
        
        // No event selected - depends on role
        if ($hrRole === 'admin') {
            return null; // Admin sees all events
        }
        
        // Manager/employee: return their assigned event IDs
        return $this->getManagerEventIds();
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
     * Get manager's assigned event IDs
     * 
     * @return array|null Array of event IDs for manager, null for admin (all events)
     */
    protected function getManagerEventIds()
    {
        $hrRole = $this->getHRRole();
        
        // Admin has access to all events
        if ($hrRole === 'admin') {
            return null;
        }
        
        // Manager and employees: get their assigned events
        $employee = Employee::where('user_id', auth()->id())->first();
        
        if (!$employee) {
            return [];
        }
        
        return $employee->events()
            ->where('events.active_flag', 1)
            ->where('employee_events.is_active', 1)
            ->pluck('events.id')
            ->toArray();
    }

    /**
     * Get employees query scoped to current event or manager's events
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getEventEmployees()
    {
        $eventId = $this->getSelectedEventId();
        $hrRole = $this->getHRRole();
        
        // If specific event selected, filter by that event
        if ($eventId) {
            return Employee::whereHas('events', function ($q) use ($eventId) {
                $q->where('events.id', $eventId)
                  ->where('employee_events.is_active', 1);
            });
        }
        
        // No event selected: admin sees all, manager sees their assigned events
        if ($hrRole === 'admin') {
            return Employee::query();
        }
        
        // Manager/employee with no event selected: filter by their assigned events
        $managerEventIds = $this->getManagerEventIds();
        if (empty($managerEventIds)) {
            return Employee::whereRaw('1 = 0'); // No results
        }
        
        return Employee::whereHas('events', function ($q) use ($managerEventIds) {
            $q->whereIn('events.id', $managerEventIds)
              ->where('employee_events.is_active', 1);
        });
    }

    /**
     * Get all available events for the current user
     * Admin/Manager: All active events
     * Employees: Only events they're assigned to
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailableEvents()
    {
        $hrRole = $this->getHRRole();
        
        // Only admin can see all events
        if ($hrRole === 'admin') {
            return Event::where('active_flag', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'event_logo']);
        }
        
        // Managers and employees only see their assigned events
        $employee = Employee::where('user_id', auth()->id())->first();
        
        if (!$employee) {
            return collect([]); // No employee record = no events
        }
        
        return $employee->events()
            ->where('events.active_flag', 1)
            ->where('employee_events.is_active', 1)
            ->orderBy('name')
            ->get(['events.id', 'events.name', 'events.event_logo']);
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
        $employee = Employee::where('user_id', $user->id)->with(['designation', 'department', 'reportingTo'])->first();
        
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
                'joinDate' => $employee->join_date,
                'manager' => $employee->reportingTo?->full_name,
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
            'joinDate' => null,
            'manager' => null,
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
        
        // Get current employee to exclude from approval counts
        $currentEmployee = \App\Models\Employee::where('user_id', auth()->id())->first();
        
        // Get pending leave requests count for selected event (excluding manager's own)
        $pendingLeavesQuery = \App\Models\EmployeeLeaveRequest::forEvent($eventId)
            ->where('status_id', \App\Models\EmployeeLeaveStatus::pendingId())
            ->active();
        
        if ($currentEmployee) {
            $pendingLeavesQuery->where('employee_id', '!=', $currentEmployee->id);
        }
        
        $pendingLeaves = $pendingLeavesQuery->count();
        
        // Get pending timesheets count for selected event (excluding manager's own)
        $pendingTimesheetsQuery = \App\Models\EmployeeTimesheet::forEvent($eventId)
            ->where('status_id', \App\Models\EmployeeTimesheetStatus::submittedId())
            ->active();
        
        if ($currentEmployee) {
            $pendingTimesheetsQuery->where('employee_id', '!=', $currentEmployee->id);
        }
        
        $pendingTimesheets = $pendingTimesheetsQuery->count();
        
        return [
            'pendingLeaves' => $pendingLeaves,
            'pendingTimesheets' => $pendingTimesheets,
        ];
    }

    /**
     * Get permission map for frontend access control
     * Returns boolean flags for each capability
     */
    protected function getPermissions(): array
    {
        $user = auth()->user();
        
        return [
            // Employee self-service (everyone)
            'viewOwnLeaves' => true,
            'createOwnLeaves' => true,
            'editOwnLeaves' => true,
            'viewOwnTimesheets' => true,
            'createOwnTimesheets' => true,
            'editOwnTimesheets' => true,
            
            // Manager permissions
            'viewTeamLeaves' => $user->can('view.team.leaves'),
            'viewTeamTimesheets' => $user->can('view.team.timesheets'),
            'approveLeaves' => $user->can('approve.leaves'),
            'approveTimesheets' => $user->can('approve.timesheets'),
            
            // HR Admin permissions
            'manageEmployees' => $user->can('manage.employees'),
            'manageLeaveTypes' => $user->can('manage.leave.types'),
            'manageDepartments' => $user->can('manage.departments'),
            'viewAllData' => $user->can('view.all.data'),
            
            // Payroll permissions (future)
            'payrollAccess' => $user->can('payroll.access'),
            'payrollReviewTimesheets' => $user->can('payroll.review.timesheets'),
            'payrollApproveTimesheets' => $user->can('payroll.approve.timesheets'),
            'payrollProcessPayments' => $user->can('payroll.process.payments'),
            'payrollGenerateBankFiles' => $user->can('payroll.generate.bank.files'),
        ];
    }

    /**
     * Get accessible modules for this user
     * Returns array of module objects with key, name, icon, url
     */
    protected function getAccessibleModules(): array
    {
        $user = auth()->user();
        $modules = [];
        
        // Everyone gets HR module (at minimum for self-service)
        $modules[] = [
            'key' => 'hr',
            'name' => 'HR',
            'icon' => 'users',
            'url' => route('hr.dashboard'),
        ];
        
        // Payroll module (future - check permission)
        if ($user->can('payroll.access')) {
            $modules[] = [
                'key' => 'payroll',
                'name' => 'Payroll',
                'icon' => 'dollar',
                'url' => '/payroll/dashboard', // route('payroll.dashboard') when created
            ];
        }
        
        // Procurement module (future)
        if ($user->can('procurement.access')) {
            $modules[] = [
                'key' => 'procurement',
                'name' => 'Procurement',
                'icon' => 'cart',
                'url' => '/procurement/dashboard',
            ];
        }
        
        // Recruiting module (future)
        if ($user->can('recruiting.access')) {
            $modules[] = [
                'key' => 'recruiting',
                'name' => 'Recruiting',
                'icon' => 'briefcase',
                'url' => '/recruiting/dashboard',
            ];
        }
        
        return $modules;
    }

    protected function getCommonProps(string $page): array
    {
        $user = auth()->user();
        
        return [
            'hrRole' => $this->getHRRole(),
            'hrPage' => $page,
            'me' => $this->me(),
            'pendingCounts' => $this->getPendingCounts(),
            
            // New permission-based props
            'can' => $this->getPermissions(),
            'userRoles' => $user->roles->pluck('name')->toArray(),
            'modules' => $this->getAccessibleModules(),
            
            ...$this->shareEventContext(),
        ];
    }
}
