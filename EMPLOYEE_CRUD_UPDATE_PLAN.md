# Employee CRUD Controller Update Plan

## Current vs New Architecture

### Current (Before)
- All fields (personal + organizational) stored in `employees_all` table
- Event filtering done via `employee_events` pivot (only tracks assignment dates)
- Organizational fields (department, designation, manager) are global, not event-specific

### New (After)
- **Personal fields** → `employees_all` table (name, DOB, contact, nationality, etc.)
- **Organizational fields** → `employee_events` pivot (department, designation, manager, agreement#)
- Employees can exist standalone (no event assignment)
- Employees can have different organizational context per event

---

## Controller Method Updates

### 1. `store()` Method
**Changes:**
- Remove organizational fields from `employees_all` create
- Add optional event assignment creation
- Handle "assign_to_event" checkbox

**Logic Flow:**
```
1. Validate personal fields only
2. Create employee record (personal data)
3. If event_id provided:
   a. Create employee_events pivot record
   b. Set organizational attributes in pivot
4. Initialize leave balances
5. Return success
```

### 2. `update()` Method
**Changes:**
- Update personal fields in `employees_all`
- Handle event assignment separately
- Support updating existing assignment or creating new one

**Logic Flow:**
```
1. Find employee
2. Validate personal fields
3. Update employee record (personal data only)
4. If event_id provided:
   a. Check if assignment exists
   b. Update or create employee_events pivot
   c. Set organizational attributes in pivot
5. Recalculate leave balances
6. Return success
```

### 3. `employee()` List Method
**Changes:**
- When viewing specific event: fetch organizational data from pivot
- When viewing "All Events": show standalone employees + assigned employees
- Display event badges for assigned employees

**Query Pattern:**
```php
// For specific event
$query->forEvent($eventId)
    ->with(['events' => function($q) use ($eventId) {
        $q->where('events.id', $eventId);
    }]);

// Access organizational data
$employee->events->first()->pivot->department_id
$employee->events->first()->pivot->designation_id
$employee->events->first()->pivot->reporting_to_id
```

### 4. New Methods to Add
- `assignToEvent()` - Assign existing employee to event with organizational context
- `updateEventAssignment()` - Update event-specific attributes
- `removeFromEvent()` - Remove event assignment
- `bulkAssignToEvent()` - Bulk assign multiple employees to event

---

## Validation Changes

### Personal Fields (employees_all)
- employee_number, first_name, last_name
- work_email_address, phone_number
- date_of_birth, gender_id, nationality_id
- national_identifier_number, passport_number
- etc.

### Event-Specific Fields (employee_events pivot)
- event_id
- agreement_number
- department_id, designation_id
- directorate_id, functional_area_id, job_level_id
- reporting_to_id
- entity_id, contract_type_id
- assigned_at, released_at

---

## Frontend Updates Required

### Employee Modal
- Section 1: Personal Information (always shown)
- Section 2: Event Assignment (checkbox-gated, optional)

### Employee List
- Display event-specific department/designation from pivot
- Show "Unassigned" badge for standalone employees
- Add bulk assignment action

---

## Migration Run Order

1. Run: `php artisan migrate` (adds columns to employee_events)
2. Run backfill migration (copies existing data)
3. Update controller methods (this file)
4. Update frontend components
5. Test all CRUD operations
6. (Future) Remove organizational columns from employees_all

---

## Backward Compatibility

During transition:
- Organizational fields exist in BOTH tables
- Queries prioritize employee_events pivot data when available
- Fall back to employees_all for standalone employees
- Bidirectional sync during update operations (temporary)

Future cleanup:
- Drop organizational columns from employees_all
- Remove fallback logic
- All queries use employee_events pivot exclusively
