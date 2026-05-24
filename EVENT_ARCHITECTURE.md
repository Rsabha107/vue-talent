# Event-Based Employee Segregation Architecture

## Overview
This document outlines the architecture for implementing event-scoped employee management, where employees are assigned to specific events and all their HR data (leaves, timesheets, etc.) is segregated by event.

## Current State Analysis

### ✅ Already Implemented
- Event model with basic fields (name, logo, active_flag)
- Event session management (select/clear event)
- Event selector UI in MeridianLayout
- Shared Inertia props: `availableEvents` and `selectedEvent`
- Employees master table (`employees_all`)
- Leave requests, leave types, timesheet foundations

### 🎯 Requirements
1. **Master-Detail Pattern**: Employees remain master data, events add context
2. **Multi-Event Assignment**: Same employee can work on multiple events
3. **Event Segregation**: All leaves, timesheets, etc. scoped by event
4. **Easy Navigation**: Clear UI showing current event context
5. **Maintain UI/UX**: Keep Meridian HR look and feel

---

## Database Schema Changes

### 1. Employee-Event Junction Table
```sql
-- Migration: create_employee_events_table.php
CREATE TABLE employee_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    
    -- Assignment dates
    assigned_at DATE NOT NULL,
    released_at DATE NULL,
    
    -- Event-specific role/position
    event_role VARCHAR(255) NULL,
    event_department_id BIGINT UNSIGNED NULL,
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT 1,
    updated_by INT DEFAULT 1,
    
    UNIQUE KEY unique_employee_event (employee_id, event_id),
    FOREIGN KEY (employee_id) REFERENCES employees_all(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_active (event_id, is_active),
    INDEX idx_employee_active (employee_id, is_active)
);
```

### 2. Add event_id to Transactional Tables

#### a) Employee Leave Requests
```sql
-- Migration: add_event_id_to_employee_leave_requests
ALTER TABLE employee_leave_requests 
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER employee_id,
    ADD FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    ADD INDEX idx_event_employee (event_id, employee_id);
```

#### b) Timesheets (if table exists)
```sql
-- Migration: add_event_id_to_employee_timesheets
ALTER TABLE employee_timesheets 
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER employee_id,
    ADD FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    ADD INDEX idx_event_employee (event_id, employee_id);
```

#### c) Employee Leave Types (Balance tracking)
```sql
-- Migration: add_event_id_to_employee_leave_types
ALTER TABLE employee_leave_types 
    ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER employee_id,
    ADD FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    ADD UNIQUE KEY unique_employee_event_leave_type (employee_id, event_id, leave_type_id);
```

#### d) Other Employee Records
Add `event_id` to any other tables that should be event-scoped:
- `employee_attachments`
- `employee_files`
- `employee_letters`
- `employee_generated_letters`
- `employee_salary` (if event-specific)
- Any other transactional data

---

## Model Relationships

### Employee Model
```php
// app/Models/Employee.php

public function events()
{
    return $this->belongsToMany(Event::class, 'employee_events')
        ->withPivot(['assigned_at', 'released_at', 'event_role', 'event_department_id', 'is_active'])
        ->withTimestamps();
}

public function activeEvents()
{
    return $this->events()->wherePivot('is_active', 1);
}

public function leaveRequests()
{
    return $this->hasMany(EmployeeLeaveRequest::class)
        ->when(session('selected_event_id'), function ($query) {
            return $query->where('event_id', session('selected_event_id'));
        });
}

// Scope for event-filtered queries
public function scopeForEvent($query, $eventId = null)
{
    $eventId = $eventId ?? session('selected_event_id');
    
    if ($eventId) {
        return $query->whereHas('events', function ($q) use ($eventId) {
            $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
        });
    }
    
    return $query;
}
```

### Event Model
```php
// app/Models/Ems/Event.php

public function employees()
{
    return $this->belongsToMany(Employee::class, 'employee_events')
        ->withPivot(['assigned_at', 'released_at', 'event_role', 'event_department_id', 'is_active'])
        ->withTimestamps();
}

public function activeEmployees()
{
    return $this->employees()->wherePivot('is_active', 1);
}

public function leaveRequests()
{
    return $this->hasMany(EmployeeLeaveRequest::class);
}
```

### EmployeeLeaveRequest Model
```php
// app/Models/EmployeeLeaveRequest.php

public function event()
{
    return $this->belongsTo(Event::class);
}

// Global scope to auto-filter by session event
protected static function booted()
{
    static::addGlobalScope('event', function ($query) {
        if ($eventId = session('selected_event_id')) {
            $query->where('event_id', $eventId);
        }
    });
}

// Scope to bypass event filtering when needed
public function scopeWithoutEventScope($query)
{
    return $query->withoutGlobalScope('event');
}
```

---

## Controller Pattern

### Base HR Controller with Event Awareness
```php
// app/Http/Controllers/MeridianHR/HRController.php

abstract class HRController extends Controller
{
    protected function getSelectedEventId(): ?int
    {
        return session('selected_event_id');
    }
    
    protected function requireEventSelection()
    {
        if (!$this->getSelectedEventId()) {
            return redirect()->route('hr.dashboard')
                ->with('error', 'Please select an event first');
        }
    }
    
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
    
    protected function shareEventContext(): array
    {
        $eventId = $this->getSelectedEventId();
        
        return [
            'selectedEvent' => $eventId,
            'eventContext' => $eventId ? Event::find($eventId) : null,
            'isEventRequired' => $this->eventRequired,
        ];
    }
}
```

### Employee Controller Updates
```php
// app/Http/Controllers/MeridianHR/EmployeeController.php

public function index()
{
    $eventId = session('selected_event_id');
    
    // Base employee query
    $query = Employee::with(['department', 'designation']);
    
    // If event selected, filter by event assignment
    if ($eventId) {
        $query->whereHas('events', function ($q) use ($eventId) {
            $q->where('events.id', $eventId)
              ->where('employee_events.is_active', 1);
        })->with(['events' => function ($q) use ($eventId) {
            $q->where('events.id', $eventId)
              ->select('events.id', 'events.name');
        }]);
    }
    
    $employees = $query->orderBy('full_name')->get()->map(function ($emp) use ($eventId) {
        return [
            'id' => $emp->id,
            'empNumber' => $emp->employee_number,
            'name' => $emp->full_name,
            'email' => $emp->work_email_address,
            'department' => $emp->department?->name,
            'designation' => $emp->designation?->name,
            'joinDate' => $emp->join_date?->format('Y-m-d'),
            'eventRole' => $eventId ? $emp->events->first()?->pivot->event_role : null,
        ];
    });
    
    return Inertia::render('MeridianHR/Employee', [
        'hrRole' => request()->query('role', 'admin'),
        'hrPage' => 'employee',
        'employees' => $employees,
        'selectedEvent' => $eventId,
        'me' => $this->me(),
    ]);
}

// New method: Assign employees to event
public function assignToEvent(Request $request, Event $event)
{
    $validated = $request->validate([
        'employee_ids' => 'required|array',
        'employee_ids.*' => 'exists:employees_all,id',
        'assigned_at' => 'required|date',
        'event_role' => 'nullable|string|max:255',
    ]);
    
    foreach ($validated['employee_ids'] as $empId) {
        $event->employees()->syncWithoutDetaching([
            $empId => [
                'assigned_at' => $validated['assigned_at'],
                'event_role' => $validated['event_role'] ?? null,
                'is_active' => 1,
            ]
        ]);
    }
    
    return back()->with('success', count($validated['employee_ids']) . ' employee(s) assigned to event');
}

// New method: Remove employee from event
public function removeFromEvent(Request $request, Event $event, Employee $employee)
{
    $event->employees()->updateExistingPivot($employee->id, [
        'is_active' => 0,
        'released_at' => now(),
    ]);
    
    return back()->with('success', 'Employee removed from event');
}
```

---

## UI/UX Implementation

### 1. Enhanced Event Selector (Already in MeridianLayout)
✅ Already implemented — just needs backend support

### 2. Event Context Indicator
Add a persistent visual indicator in the layout:

```vue
<!-- resources/js/Layouts/MeridianLayout.vue -->
<template>
  <div class="meridian-app" :data-collapsed="collapsed ? '1' : undefined">
    
    <!-- Event Context Banner (add after sidebar) -->
    <div v-if="selectedEvent" class="mhr-event-banner">
      <div class="mhr-event-banner__content">
        <AppIcon name="calendar" :size="16" />
        <span class="mhr-event-banner__label">Event:</span>
        <strong>{{ activeEventLabel }}</strong>
      </div>
      <button @click="eventSelectorOpen = true" class="mhr-event-banner__change">
        Change Event
      </button>
    </div>
    
    <!-- Existing sidebar and content -->
    <aside class="mhr-sidebar">...</aside>
    <!-- ... -->
  </div>
</template>

<style>
.mhr-event-banner {
  position: fixed;
  top: 0;
  left: 240px;
  right: 0;
  height: 40px;
  background: var(--mhr-accent);
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--mhr-pad);
  z-index: 100;
  transition: left 0.2s;
}

.meridian-app[data-collapsed] .mhr-event-banner {
  left: 60px;
}

.mhr-event-banner__content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.mhr-event-banner__change {
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  padding: 4px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.mhr-event-banner__change:hover {
  background: rgba(255,255,255,0.3);
}

/* Adjust main content when banner is visible */
.meridian-app .mhr-content {
  margin-top: 40px;
}
</style>
```

### 3. Employee Assignment Interface

New component: `resources/js/Components/MeridianHR/EmployeeEventAssignment.vue`

```vue
<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  event: Object,
  assignedEmployees: Array,
  availableEmployees: Array,
})

const showAssignModal = ref(false)
const selectedEmployeeIds = ref([])
const assignmentDate = ref(new Date().toISOString().split('T')[0])
const eventRole = ref('')

function assignEmployees() {
  router.post(route('hr.events.assign-employees', props.event.id), {
    employee_ids: selectedEmployeeIds.value,
    assigned_at: assignmentDate.value,
    event_role: eventRole.value,
  }, {
    onSuccess: () => {
      showAssignModal.value = false
      selectedEmployeeIds.value = []
      eventRole.value = ''
    }
  })
}

function removeEmployee(empId) {
  if (confirm('Remove this employee from the event?')) {
    router.delete(route('hr.events.remove-employee', {
      event: props.event.id,
      employee: empId,
    }))
  }
}
</script>

<template>
  <div class="mhr-card">
    <div class="mhr-card__header">
      <h3>Event Team</h3>
      <button @click="showAssignModal = true" class="mhr-btn mhr-btn--primary">
        <AppIcon name="plus" :size="16" />
        Add Employees
      </button>
    </div>
    
    <div class="mhr-table-wrap">
      <table class="mhr-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Email</th>
            <th>Department</th>
            <th>Event Role</th>
            <th>Assigned Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in assignedEmployees" :key="emp.id">
            <td>{{ emp.name }}</td>
            <td>{{ emp.email }}</td>
            <td>{{ emp.department }}</td>
            <td>{{ emp.eventRole || '—' }}</td>
            <td>{{ emp.assignedAt }}</td>
            <td>
              <button @click="removeEmployee(emp.id)" class="mhr-btn mhr-btn--ghost">
                <AppIcon name="trash" :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Assignment Modal -->
  <div v-if="showAssignModal" class="mhr-modal" @click.self="showAssignModal = false">
    <div class="mhr-modal__content">
      <div class="mhr-modal__header">
        <h3>Assign Employees to {{ event.name }}</h3>
        <button @click="showAssignModal = false" class="mhr-modal__close">×</button>
      </div>
      
      <div class="mhr-modal__body">
        <div class="mhr-field">
          <label>Select Employees</label>
          <select v-model="selectedEmployeeIds" multiple class="mhr-select" style="height: 200px;">
            <option v-for="emp in availableEmployees" :key="emp.id" :value="emp.id">
              {{ emp.name }} — {{ emp.department }}
            </option>
          </select>
          <small>Hold Ctrl/Cmd to select multiple</small>
        </div>
        
        <div class="mhr-field">
          <label>Assignment Date</label>
          <input v-model="assignmentDate" type="date" class="mhr-input" />
        </div>
        
        <div class="mhr-field">
          <label>Event Role (Optional)</label>
          <input v-model="eventRole" type="text" class="mhr-input" 
                 placeholder="e.g., Team Lead, Coordinator" />
        </div>
      </div>
      
      <div class="mhr-modal__footer">
        <button @click="showAssignModal = false" class="mhr-btn mhr-btn--ghost">Cancel</button>
        <button @click="assignEmployees" class="mhr-btn mhr-btn--primary" 
                :disabled="!selectedEmployeeIds.length">
          Assign {{ selectedEmployeeIds.length }} Employee(s)
        </button>
      </div>
    </div>
  </div>
</template>
```

### 4. Event Page Enhancement

Update `resources/js/Pages/MeridianHR/Event.vue` to include employee assignment:

```vue
<script setup>
import { ref } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import EmployeeEventAssignment from '@/Components/MeridianHR/EmployeeEventAssignment.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole: String,
  events: Array,
  venues: Array,
  statuses: Array,
})

const selectedEventForAssignment = ref(null)

function showEmployeeAssignment(event) {
  selectedEventForAssignment.value = event
  // Load assigned and available employees via API or pass in props
}
</script>

<template>
  <!-- Existing event list -->
  <div class="mhr-content">
    <!-- ... existing event CRUD UI ... -->
    
    <!-- Add assignment button to each event row -->
    <button @click="showEmployeeAssignment(event)" class="mhr-btn mhr-btn--outline">
      <AppIcon name="users" :size="16" />
      Manage Team
    </button>
  </div>
  
  <!-- Employee Assignment Modal/Panel -->
  <EmployeeEventAssignment 
    v-if="selectedEventForAssignment"
    :event="selectedEventForAssignment"
    :assigned-employees="selectedEventForAssignment.employees"
    :available-employees="availableEmployees"
    @close="selectedEventForAssignment = null"
  />
</template>
```

---

## Navigation Flow

### User Journey: Event-Based Workflow

1. **Login → Dashboard**
   - User sees dashboard with event selector in header
   - If no event selected: show "Select an event to continue" prompt
   - List available events with visual cards

2. **Select Event**
   - Click event selector dropdown (already in layout)
   - Choose event from `availableEvents`
   - Session stores `selected_event_id`
   - All HR pages now filter by this event

3. **View Employees**
   - Navigate to "Employees" (existing nav)
   - See only employees assigned to selected event
   - Can view all employees in "master mode" (admin only)

4. **Manage Leaves/Timesheets**
   - All leave requests auto-tagged with current `event_id`
   - Leave balance is event-specific
   - Historical data grouped by event

5. **Switch Events**
   - Click event selector again
   - Choose different event
   - Context switches instantly
   - All data updates to new event scope

### Admin Navigation Enhancements

Add "Event Management" section to admin nav:

```javascript
// In MeridianLayout.vue NAV.admin
{
  group: 'Events',
  items: [
    { id: 'events', label: 'All Events', icon: 'calendar' },
    { id: 'event-employees', label: 'Event Assignments', icon: 'users' },
    { id: 'cross-event-reports', label: 'Cross-Event Reports', icon: 'chart' },
  ]
}
```

---

## Middleware for Event Protection

Create middleware to ensure event is selected for certain pages:

```php
// app/Http/Middleware/RequireEventSelection.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireEventSelection
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('selected_event_id')) {
            return redirect()->route('hr.dashboard')
                ->with('error', 'Please select an event to continue');
        }
        
        return $next($request);
    }
}
```

Apply to routes:

```php
// routes/web.php

Route::middleware(['auth', 'require-event'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/leave', [EmployeeLeaveRequestController::class, 'index'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'index'])->name('timesheet');
    // ... other event-scoped routes
});
```

---

## Implementation Checklist

### Phase 1: Database Foundation (Day 1-2)
- [ ] Create `employee_events` migration and table
- [ ] Add `event_id` to `employee_leave_requests`
- [ ] Add `event_id` to `employee_leave_types`
- [ ] Add `event_id` to `employee_timesheets` (if exists)
- [ ] Add `event_id` to other relevant tables
- [ ] Run migrations and test

### Phase 2: Models & Relationships (Day 2-3)
- [ ] Update `Employee` model with event relationships
- [ ] Update `Event` model with employee relationships
- [ ] Add global scopes to transactional models
- [ ] Update `EmployeeLeaveRequest` model
- [ ] Test model queries in Tinker

### Phase 3: Controllers & Routes (Day 3-5)
- [ ] Create base `HRController` with event-aware methods
- [ ] Update `EmployeeController` for event filtering
- [ ] Add employee assignment endpoints to `EventController`
- [ ] Update `EmployeeLeaveRequestController` to use event scope
- [ ] Add middleware `RequireEventSelection`
- [ ] Register new routes

### Phase 4: UI Components (Day 5-7)
- [ ] Enhance event selector in `MeridianLayout`
- [ ] Add event context banner to layout
- [ ] Create `EmployeeEventAssignment` component
- [ ] Update `Event.vue` page with assignment interface
- [ ] Add "No event selected" states to relevant pages
- [ ] Test all event switching flows

### Phase 5: Data Migration (Day 7-8)
- [ ] Write seeder to assign existing employees to events
- [ ] Backfill `event_id` on existing leave requests
- [ ] Verify data integrity
- [ ] Test with production-like data volume

### Phase 6: Testing & Polish (Day 8-10)
- [ ] Write feature tests for event scoping
- [ ] Test multi-event assignment scenarios
- [ ] Test event switching across all pages
- [ ] Performance test with large datasets
- [ ] Polish UI transitions and loading states
- [ ] Update documentation

---

## Sample API Endpoints

```php
// Routes for employee-event management

Route::prefix('hr')->middleware('auth')->name('hr.')->group(function () {
    
    // Event Management
    Route::get('/events/{event}/employees', [EventController::class, 'showEmployees'])
        ->name('events.employees');
    
    Route::post('/events/{event}/assign-employees', [EventController::class, 'assignEmployees'])
        ->name('events.assign-employees');
    
    Route::delete('/events/{event}/employees/{employee}', [EventController::class, 'removeEmployee'])
        ->name('events.remove-employee');
    
    Route::get('/api/employees/unassigned/{event}', [EmployeeController::class, 'unassignedToEvent'])
        ->name('api.employees.unassigned');
    
    // Cross-event employee view (admin only)
    Route::get('/employees/all-events', [EmployeeController::class, 'crossEventView'])
        ->name('employees.all-events')
        ->middleware('role:admin');
});
```

---

## Benefits of This Architecture

### ✅ Maintains Master Data Integrity
- Employees exist once in `employees_all`
- All master data (name, email, etc.) is centralized
- Easy to update employee information globally

### ✅ Clean Event Segregation
- Each event's data is isolated via `event_id`
- No data leakage between events
- Clear audit trail of which employee worked which event

### ✅ Multi-Event Support
- Same employee can be in multiple events
- Different roles per event via pivot table
- Flexible assignment and release dates

### ✅ Existing UI Preserved
- Meridian HR design system unchanged
- Event selector already in layout
- Minimal visual disruption
- Familiar navigation patterns

### ✅ Performance Optimized
- Proper indexes on junction table
- Scoped queries reduce dataset size
- Session-based event context (no repeated lookups)

### ✅ Easy to Extend
- Add more event-specific fields to pivot
- Event-based permissions/roles
- Event lifecycle management
- Cross-event reporting

---

## Advanced Features (Future)

### Event Templates
- Define "event types" with default settings
- Quickly create new events from templates
- Pre-assign team structures

### Event Cloning
- Clone event with all assignments
- Useful for recurring events
- Optionally clone leave balances

### Cross-Event Reporting
- Compare employee performance across events
- Resource utilization analysis
- Event profitability per employee

### Event-Based Permissions
- Role within event (lead, member, viewer)
- Restrict data access by event assignment
- Manager can only see their event's employees

---

## Questions & Considerations

1. **Historical Data**: Should past employees remain visible in old events?
   - **Recommendation**: Yes, via `released_at` date. Use `is_active` flag for current assignments.

2. **Leave Balance**: Per-event or global?
   - **Recommendation**: Per-event via `employee_leave_types` with `event_id`. Allows different allowances per event.

3. **Event Overlap**: What if employee has leave approved in Event A during Event B dates?
   - **Recommendation**: Show warning in UI, allow admin override. Add validation if strict enforcement needed.

4. **No Event Selected**: Should some pages work without event selection?
   - **Recommendation**: Dashboard and profile yes. Leaves/timesheets require event selection.

5. **Employee Master List**: Can admin see all employees regardless of event?
   - **Recommendation**: Yes, add "All Employees" view for admins that bypasses event scope.

---

## Success Metrics

- ✅ All employee data correctly scoped by event
- ✅ No performance degradation (queries < 500ms)
- ✅ User can switch events in < 2 seconds
- ✅ Zero data leakage between events
- ✅ UI remains intuitive and familiar
- ✅ Mobile responsive (Meridian HR is mobile-first)

---

## Support & Maintenance

### Regular Checks
- Monitor query performance with event scoping
- Review event assignment data quality
- Check for orphaned records (no event assigned)

### Future Migrations
- Plan for soft-delete vs hard-delete of events
- Archive old events but keep data
- Data retention policy per event

---

## Conclusion

This architecture provides:
1. **Clean separation** between master employee data and event-specific context
2. **Flexible assignment** allowing employees to work multiple events
3. **Intuitive UX** with clear event selection and context awareness
4. **Scalable design** that maintains performance as data grows
5. **Preserved design system** keeping the beautiful Meridian HR UI intact

The implementation is phased to allow incremental rollout and testing, minimizing risk while delivering a powerful event-based employee management system.
