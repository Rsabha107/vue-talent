# EmployeeController CRUD Implementation Summary

## Changes Completed

### 1. `store()` Method - Create Employee
**Status:** ✅ Complete

**Changes:**
- Separated validation into two groups: personal fields and event-specific fields
- Personal fields saved to `employees_all` table only
- Added `assign_to_event` checkbox support
- When checked, validates and stores event organizational attributes in `employee_events` pivot
- Returns appropriate success message based on whether event assignment was made

**Key Features:**
- Employees can exist standalone (no event assignment required)
- Event assignment is optional during creation
- All organizational attributes (department, designation, manager, agreement#) stored in pivot
- Leave balances initialized only if assigned to event

### 2. `update()` Method - Update Employee
**Status:** ✅ Complete

**Changes:**
- Separated validation into personal and event-specific fields
- Updates only personal data in `employees_all` table
- Handles event assignment separately via pivot operations
- Checks if assignment exists before updating (update vs create)
- Supports creating new event assignment or updating existing one

**Key Features:**
- Can update employee without touching event assignments
- Can update/create event assignment with organizational context
- Uses `updateExistingPivot()` for existing assignments
- Uses `attach()` for new assignments

### 3. `employee()` List Method - Display Employees
**Status:** ✅ Complete

**Changes:**
- Removed old `with(['department', 'designation', ...])` eager loading (no longer in employees table)
- Updated to load pivot data with `withPivot()` including all organizational columns
- Fetches organizational data from pivot instead of employee model
- Lazy loads related models only when pivot data exists
- Shows "Unassigned" for employees with no event assignment in "All Events" view

**Key Features:**
- When viewing specific event: displays event-specific organizational attributes from pivot
- When viewing "All Events": shows standalone employees with "Unassigned" labels
- Pivot columns accessed via `$event->pivot->department_id`, `$event->pivot->designation_id`, etc.
- Related models loaded on-demand: `Department::find($departmentId)`

### 4. `assignToEvent()` Method - Bulk Assign
**Status:** ✅ Complete

**Changes:**
- Added validation for organizational fields (department, designation, manager, etc.)
- Expanded pivot data to include all event-specific attributes
- Same organizational fields applied to all selected employees in bulk operation

**Key Features:**
- Bulk assignment with full organizational context
- Prevents duplicate assignments
- Returns count of assigned vs already-assigned employees

---

## Database Schema Changes Required

### employee_events Pivot Table (New Columns)
The following columns were added via migration:
- `agreement_number` (string, nullable)
- `department_id` (integer, nullable)
- `designation_id` (integer, nullable)
- `directorate_id` (integer, nullable)
- `functional_area_id` (integer, nullable)
- `job_level_id` (integer, nullable)
- `reporting_to_id` (integer, nullable)
- `entity_id` (integer, nullable)
- `contract_type_id` (integer, nullable)

### Indexes Added
- `idx_event_department` on `department_id`
- `idx_event_designation` on `designation_id`
- `idx_event_manager` on `reporting_to_id`

---

## Frontend Integration Points

### Employee Modal (resources/js/Pages/MeridianHR/Employee.vue)
**Required changes:**
1. Split form into two sections:
   - Section 1: Personal Information (always visible)
   - Section 2: Event Assignment (checkbox-gated, optional)

2. Add "Assign to Event" checkbox:
   ```vue
   <input type="checkbox" v-model="form.assign_to_event">
   ```

3. Conditionally show event fields when checkbox is checked:
   ```vue
   <div v-if="form.assign_to_event">
     <!-- event_id, department_id, designation_id, etc. -->
   </div>
   ```

4. Update form data structure:
   ```js
   const form = useForm({
     // Personal fields
     first_name: '',
     last_name: '',
     employee_number: '',
     work_email_address: '',
     // ... other personal fields

     // Event assignment flag
     assign_to_event: false,

     // Event-specific fields (optional)
     event_id: null,
     agreement_number: '',
     department_id: null,
     designation_id: null,
     reporting_to_id: null,
     assigned_at: '',
     released_at: '',
   })
   ```

### Employee List Display
**Required changes:**
1. Show "Unassigned" badge for employees with no event assignment
2. Display event-specific department/designation from pivot data
3. Update table columns to reflect pivot-based organizational data

### Bulk Assignment Modal
**Required changes:**
1. Create modal with organizational fields
2. Allow selecting multiple employees + one event + organizational context
3. Submit to `assignToEvent()` route with all data

---

## Migration Run Commands

```bash
# 1. Run migration to add columns
php artisan migrate

# 2. Backfill existing data
php artisan migrate --path=database/migrations/2026_05_30_110000_backfill_employee_events_organizational_data.php

# 3. Verify data
php artisan tinker
>>> \App\Models\Employee::with('events')->first()->events->first()->pivot
```

---

## Testing Checklist

### Create Operations
- [ ] Create employee without event assignment (standalone)
- [ ] Create employee with event assignment and organizational attributes
- [ ] Verify personal data in `employees_all` table
- [ ] Verify event data in `employee_events` pivot table
- [ ] Check leave balances are initialized only when assigned to event

### Update Operations
- [ ] Update personal information only (no event change)
- [ ] Update event assignment organizational attributes
- [ ] Create new event assignment for existing employee
- [ ] Verify updates affect correct tables

### Display Operations
- [ ] View employees in specific event context
- [ ] Verify organizational data loaded from pivot
- [ ] View "All Events" and check "Unassigned" labels
- [ ] Check employee details modal shows correct data

### Bulk Assignment
- [ ] Assign multiple employees to event with organizational context
- [ ] Verify pivot records created correctly
- [ ] Check duplicate prevention works
- [ ] Test with mixed (some assigned, some unassigned) selection

### Import (Next Phase)
- [ ] Import employees without event assignment
- [ ] Import employees with event assignment
- [ ] Verify correct table population

---

## Known Issues / Future Work

### Phase 2 (Next):
1. Update Employee.vue modal UI structure (personal section + event section)
2. Add unassigned badge styling and display logic
3. Create bulk assignment modal UI

### Phase 3 (Future):
1. Update EmployeesImport class to support optional event assignment
2. Add event selection dropdown to import interface
3. Create CSV template with event columns (optional)

### Phase 4 (Cleanup):
1. Remove organizational columns from `employees_all` table (after confirming all queries updated)
2. Drop old indexes
3. Update remaining controllers that query organizational attributes

---

## Rollback Plan

If issues occur:
1. The old columns still exist in `employees_all` table (not removed yet)
2. Queries can be reverted to use employee model relationships
3. Migrations can be rolled back:
   ```bash
   php artisan migrate:rollback --step=2
   ```

---

## Documentation References

- **Architecture Overview:** `EVENT_SCOPED_ARCHITECTURE.md`
- **Migration Files:** 
  - `database/migrations/2026_05_30_100000_add_organizational_attributes_to_employee_events.php`
  - `database/migrations/2026_05_30_110000_backfill_employee_events_organizational_data.php`
- **Model Updates:** `app/Models/Employee.php` (updated relationships and helper methods)
