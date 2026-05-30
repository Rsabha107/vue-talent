# Event-Scoped Employee Architecture

**Date:** May 30, 2026  
**Status:** Implemented - Migration Ready

## Overview

The system now supports **event-specific organizational attributes** for employees. An employee can be assigned to multiple events simultaneously with different:
- Departments
- Designations (roles/titles)
- Managers (reporting structure)
- Agreement numbers
- Entities
- Contract types

This allows employees to work on overlapping events with different organizational contexts.

---

## Architecture Changes

### Database Structure

#### `employees_all` table
**Purpose:** Personal/identity data that doesn't vary by event

**Contains:**
- Employee number, full name, DOB, gender
- National ID, passport, Iqama numbers
- Contact information (email, phone)
- Nationality, marital status, language
- Profile photo

**Does NOT contain (moved to employee_events):**
- ~~department_id~~
- ~~designation_id~~
- ~~reporting_to_id~~
- ~~agreement_number~~
- ~~entity_id~~
- ~~contract_type_id~~
- ~~directorate_id~~
- ~~functional_area_id~~
- ~~job_level_id~~

#### `employee_events` pivot table
**Purpose:** Event-specific assignments and organizational context

**New columns added:**
- `agreement_number` - Contract number for this event
- `entity_id` - Legal entity for this event
- `contract_type_id` - Contract type for this event
- `department_id` - Department for this event
- `designation_id` - Role/title for this event
- `directorate_id` - Directorate for this event
- `functional_area_id` - Functional area for this event
- `job_level_id` - Job level for this event
- `reporting_to_id` - Manager for this event

**Existing columns:**
- `employee_id`, `event_id`
- `assigned_at`, `released_at`
- `is_active`

---

## Migration Files

### 1. `2026_05_30_100000_add_organizational_attributes_to_employee_events.php`
Adds organizational columns to `employee_events` table.

### 2. `2026_05_30_110000_backfill_employee_events_organizational_data.php`
Copies existing organizational data from `employees_all` to `employee_events` for existing assignments.

**To run:**
```bash
php artisan migrate
```

---

## Model Updates

### Employee Model

#### Updated Relationships

```php
// Events with organizational pivot data
public function events()
{
    return $this->belongsToMany(Event::class, 'employee_events')
        ->withPivot([
            'assigned_at', 'released_at', 'is_active',
            'agreement_number', 'entity_id', 'contract_type_id',
            'department_id', 'designation_id', 'directorate_id',
            'functional_area_id', 'job_level_id', 'reporting_to_id',
        ])
        ->withTimestamps();
}
```

#### New Helper Methods

```php
// Get event assignment with organizational context
$assignment = $employee->getEventAssignment($eventId);
$department = $assignment->pivot->department_id;
$manager = $assignment->pivot->reporting_to_id;

// Get event-specific related models
$department = $employee->getEventDepartment($eventId);
$designation = $employee->getEventDesignation($eventId);
$manager = $employee->getEventManager($eventId);

// Scope for event filtering
Employee::forEvent($eventId)->get();
```

---

## Usage Patterns

### Query Examples

#### Get employee with event-specific attributes

```php
$employee = Employee::with(['events' => function($q) use ($eventId) {
    $q->where('events.id', $eventId);
}])->find($employeeId);

$assignment = $employee->events->first();
$department = Department::find($assignment->pivot->department_id);
$manager = Employee::find($assignment->pivot->reporting_to_id);
```

#### Get all employees for an event with organizational data

```php
$employees = Employee::forEvent($eventId)
    ->with(['events' => function($q) use ($eventId) {
        $q->where('events.id', $eventId);
    }])
    ->get();

// Access organizational attributes
foreach ($employees as $employee) {
    $assignment = $employee->events->first();
    echo $assignment->pivot->department_id;
    echo $assignment->pivot->designation_id;
    echo $assignment->pivot->reporting_to_id;
}
```

#### Assign employee to event with organizational context

```php
$employee->events()->attach($eventId, [
    'assigned_at' => now(),
    'is_active' => 1,
    'department_id' => $departmentId,
    'designation_id' => $designationId,
    'reporting_to_id' => $managerId,
    'agreement_number' => 'AGR-2024-001',
    'entity_id' => $entityId,
    'contract_type_id' => $contractTypeId,
]);
```

#### Update event assignment

```php
$employee->events()->updateExistingPivot($eventId, [
    'department_id' => $newDepartmentId,
    'reporting_to_id' => $newManagerId,
]);
```

---

## Controller Updates Required

Controllers that currently query organizational attributes need updates:

### Pattern 1: Event-filtered queries (already working)

```php
// Current (still works, but uses old columns in employees_all)
$employees = Employee::whereHas('events', function($q) use ($eventId) {
    $q->where('events.id', $eventId);
})->with('department', 'designation')->get();

// Updated (uses event-specific attributes)
$employees = Employee::forEvent($eventId)
    ->with(['events' => function($q) use ($eventId) {
        $q->where('events.id', $eventId);
    }])
    ->get();

// Access in view
foreach ($employees as $employee) {
    $dept = Department::find($employee->events->first()->pivot->department_id);
}
```

### Pattern 2: Creating/updating employees with event assignment

```php
// Create employee
$employee = Employee::create([
    'first_name' => $request->first_name,
    'last_name' => $request->last_name,
    // ... personal fields only
]);

// Assign to event (if event selected)
if ($request->event_id) {
    $employee->events()->attach($request->event_id, [
        'assigned_at' => $request->assigned_at ?? now(),
        'is_active' => 1,
        'department_id' => $request->department_id,
        'designation_id' => $request->designation_id,
        'reporting_to_id' => $request->reporting_to_id,
        'agreement_number' => $request->agreement_number,
        'entity_id' => $request->entity_id,
        'contract_type_id' => $request->contract_type_id,
    ]);
}
```

---

## Import Functionality

### Excel Import with Optional Event Assignment

```php
class EmployeesImport implements ToModel
{
    private $eventId;
    
    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
    }
    
    public function model(array $row)
    {
        // Create employee (personal data)
        $employee = Employee::create([
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            // ... other personal fields
        ]);
        
        // Assign to event if specified
        if ($this->eventId) {
            $employee->events()->attach($this->eventId, [
                'assigned_at' => $row['assigned_date'] ?? now(),
                'is_active' => 1,
                'department_id' => $this->findDepartmentId($row['department']),
                'designation_id' => $this->findDesignationId($row['designation']),
                'reporting_to_id' => $this->findManagerId($row['manager_email']),
                'agreement_number' => $row['agreement_number'],
            ]);
        }
        
        return $employee;
    }
}
```

---

## Frontend Updates Required

### Add/Edit Employee Modal

**Structure:**
```
┌─── Personal Information (always shown)
│   Employee Number, Name, DOB, Gender, etc.
│
├─── Event Assignment (optional, checkbox-gated)
│   ☐ Assign to event
│   Event Dropdown
│   Department, Designation, Manager
│   Agreement Number, Contract Type
│   Assigned Date
```

### Employee List Views

**Event-filtered views** (current behavior):
- Show only employees assigned to selected event
- Display event-specific department, designation, manager

**All employees view** (admin):
- Show all employees including unassigned
- Display event tags or "Unassigned" badge

---

## Benefits

1. **Supports overlapping events** - Employee can work on multiple projects simultaneously with different organizational contexts
2. **Time-accurate** - Historical data preserved per event assignment
3. **Flexible** - Employees can exist without event assignments (talent pool, pre-onboarding)
4. **Clean separation** - Personal data vs organizational data clearly separated
5. **Minimal breaking changes** - Most event-filtered queries continue to work

---

## Next Steps

1. ✅ Run migrations (`php artisan migrate`)
2. ✅ Update Employee CRUD controller to use new structure
3. ⏳ Update Employee list/detail pages to show event-specific attributes
4. ⏳ Update import functionality with event selection
5. ⏳ Add bulk assignment UI for unassigned employees
6. ⏳ Update existing controllers that query organizational attributes
7. ⏳ Remove organizational columns from `employees_all` table (future cleanup)

**Phase 2 Status:** ✅ Complete
- `EmployeeController::store()` updated - supports optional event assignment
- `EmployeeController::update()` updated - handles personal + event data separately
- `EmployeeController::employee()` updated - displays pivot-based organizational data
- `EmployeeController::assignToEvent()` updated - bulk assign with full organizational context
- See `EMPLOYEE_CONTROLLER_IMPLEMENTATION.md` for detailed implementation notes

---

## Backward Compatibility

**Phase 1 (Current):**
- Organizational columns exist in both `employees_all` AND `employee_events`
- Queries using `employees.department_id` still work
- Data is synced bidirectionally during transition period

**Phase 2 (Future):**
- Drop organizational columns from `employees_all`
- All queries must use `employee_events` pivot
- Run comprehensive testing before this step

---

## Testing Checklist

- [ ] Create standalone employee (no event assignment)
- [ ] Assign employee to single event
- [ ] Assign employee to multiple overlapping events
- [ ] Update event-specific attributes
- [ ] Filter employees by event
- [ ] Import employees with event assignment
- [ ] Import employees without event assignment
- [ ] Bulk assign unassigned employees to event
- [ ] Verify historical data preserved per event
