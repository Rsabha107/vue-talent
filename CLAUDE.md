# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Laravel 13** / PHP 8.3+ backend
- **Vue 3.4** + **Inertia.js 2.3** (no API layer — controllers render Vue pages directly via Inertia)
- **Vite 8** for asset bundling
- **Spatie Laravel Permission** for role-based access control
- **Bootstrap 5.3** + custom **Meridian HR** design system (see CSS section below)

## Commands

```bash
# Frontend dev server (Vite only)
npm run dev

# Frontend production build
npm run build

# Full stack dev (Laravel + Queue + Vite via concurrently)
composer run dev

# Run tests (PHPUnit, SQLite in-memory)
composer run test

# Single test class
php artisan test --filter=YourTestClass

# Migrations
php artisan migrate
php artisan migrate:fresh --seed
```

## Architecture

### Two UI Systems

The app has two distinct frontend interfaces that coexist:

1. **Legacy admin UI** — Bootstrap 5 tables, jQuery-driven sidebar/metisMenu, pages under `resources/js/Pages/{Users,Events,Venues,Security}/`. Uses `AuthenticatedLayout.vue`.

2. **Meridian HR UI** — Custom design system, pages under `resources/js/Pages/MeridianHR/`. Uses `MeridianLayout.vue`. This is the actively developed interface.

### Inertia Page Resolution

`app.js` resolves pages from `./Pages/{Name}.vue`. Each Inertia page declares its layout with `defineOptions({ layout: MeridianLayout })` (or `AuthenticatedLayout`) — there is no `app.blade.php` layout wrapping per-page.

### Route Structure

`routes/web.php` has two main sections:

- **Main routes** (middleware `auth`) — users, roles, permissions, events, venues, profile
- **HR routes** (`/hr/*`, name prefix `hr.`, middleware `auth`) — all Meridian HR pages (dashboard, leave, timesheet, employee, leave-types, etc.)

**Dual-route pattern** for Bootstrap Table pages: a GET `/resource` route renders the Inertia page, and a GET `/api/resource` route returns JSON for the Bootstrap Table to consume via AJAX. Meridian HR pages use Inertia props instead and do not follow this pattern.

### Shared Inertia Props

`HandleInertiaRequests.php` shares `auth.user` and `flash.{success,error}` to every page. HR controllers additionally pass `hrRole` and `hrPage` which `MeridianLayout` uses for navigation state and access control.

### Legacy Script Lifecycle

`app.js` calls `initLegacyScripts()` after every Inertia navigation (`router.on('finish')`). This re-initialises jQuery plugins (metisMenu sidebar, Waves ripple, mobile sidebar toggle) that lose state on SPA navigation. Do not remove these calls.

## Meridian HR Design System

All Meridian HR pages use CSS custom properties defined in `resources/css/meridian.css`, scoped to `.meridian-app`. Key tokens:

- **Colours**: `--mhr-bg`, `--mhr-surface`, `--mhr-line`, `--mhr-ink` (and `-2/-3/-4` variants), `--mhr-accent`, `--mhr-danger`, `--mhr-warn`, `--mhr-brand`
- **Spacing**: `--mhr-pad`, `--mhr-gap`, `--mhr-r` (border-radius)
- **Utility classes**: `.mhr-btn`, `.mhr-btn--primary/ghost/danger/outline`, `.mhr-input`, `.mhr-select`, `.mhr-field`, `.mhr-card`, `.mhr-table`, `.mhr-modal`, `.mhr-badge`, `.mhr-badge--success/neutral/warn`, `.mhr-toast`

**Do not use Bootstrap classes or Tailwind utilities inside Meridian HR pages** — use `--mhr-*` tokens and the `.mhr-*` utility classes only.

### Shared Components (`resources/js/Components/MeridianHR/`)

- **`AppIcon`** — inline SVG icon system. Pass `name` (string key from the internal `ICONS` map) and optional `size` (number, default 18). Add new icons to the `ICONS` object inside the component.
- **`AppAvatar`** — user avatar with initials + colour hash.
- **`StatusPill`** — coloured status badge.

## Data Patterns

### Controller → Page props

HR controllers return data shaped for the page via `Inertia::render()`. Eligibility lookup lists (`contractTypes`, `genders`, `departments`, `designations`) are passed alongside `leaveTypes` so the page never makes secondary API calls.

### Leave Eligibilities

`leave_eligibilities` is a junction table (`leave_type_id` + one nullable FK per dimension). Each selected value is stored as its own row with the other FK columns null. The unique constraint covers all five columns together. Sync via delete-all + re-insert — see `LeaveTypeController::syncEligibilities()`.

### Models

Singular model names (`Employee`, `LeaveType`), plural snake_case table names (`employee_leave_types`, `leave_eligibilities`). Most models use `active_flag` (integer 0/1) rather than soft deletes. Deactivation is done by setting `active_flag = 0`.

### Event Filtering

The system supports multi-event architecture where employees can be associated with multiple events via the `employee_events` pivot table (has `is_active` flag). Selected event is stored in session via `session('selected_event_id')` and accessed via `BaseHRController::getSelectedEventId()`.

**Emergency Contacts** respect event filtering:
- Admin/manager views: Only show contacts for employees active in the selected event using `whereHas('employee.events', function($q) use ($eventId) { $q->where('events.id', $eventId); })`
- Employee dropdown: Uses `Employee::forEvent($eventId)` scope to show only event-active employees
- CRUD operations: Validate that the employee belongs to the event before creating/updating/deleting contacts
- Employee role: Shows fixed employee name (non-searchable) for their own contacts only

**Type Coercion**: All foreign key fields (e.g., `country_of_birth`, `gender_id`, `marital_status_id`, `nationality_id`) must be explicitly converted to `Number()` in Vue forms before submission, as v-model returns strings but the backend expects integers.

## Design Reference: HTML Prototype

The canonical design reference is `C:\Users\r.sabha\Downloads\Meridian HR _standalone_ (5).html` — a self-contained React prototype (compiled, not source). Open in a browser; use the **Tweaks panel** (bottom-right corner) to switch between `employee`, `manager`, and `admin` roles. There are also theme, density, accent colour, and font options there.

### Pages in the prototype

`dashboard`, `leave`, `timesheet`, `approve-leave`, `approve-time`, `documents`, `payslips`, `directory`, `profile`

### Dashboard layouts by role

**Employee** — 4-col stat row + 2-col (1.5fr / 1fr) cards:
- Stat tiles: Annual leave (remaining/total + progress bar), Sick leave, Personal leave, Next pay (dark green gradient, amount + date)
- Leave tile detail: progress bar split into used (dark green) + pending (light green); delta shows "X used · Y pending"
- Cards: "Upcoming time off" (your team's next 14 days) + "Recent activity" (personal feed, last 5 items with avatars)

**Manager** — 4-col stat row + 2-col (1.5fr / 1fr) cards:
- Stat tiles: Leave requests (clickable → approvals queue), Timesheets (clickable → timesheet approvals), Team out today, Team utilization (% with delta)
- Cards: "Pending leave approvals" (list with overlap warnings + Review button) + "Team out next 14 days"

**Admin** — 4-col stat row + 2-col charts + full-width activity log:
- Stat tiles: Headcount, On leave today (with type breakdown), Pending requests (clickable), Payroll · Month Year
- Charts: "Headcount by department" (horizontal bar, dept color-coded) + "Org utilization" (vertical bar, last 6 months)
- Full-width: "Recent system activity" log (cross-org, last 24 h, with Export button)

### CSS class mapping (prototype → our implementation)

| Prototype class | Our `.mhr-*` equivalent |
|---|---|
| `.stat` / `.stat__label` / `.stat__value` / `.stat__unit` / `.stat__delta` | `.mhr-stat` and its `__*` variants |
| `.card` / `.card__hd` / `.card__body` | `.mhr-card` / `.mhr-card__hd` / `.mhr-card__body` |
| `.grid-4` / `.grid-2` | `.mhr-grid-4` / `.mhr-grid-2` |
| `.pill--success/warn/danger/info` | `.mhr-pill--success/warn/danger` |
| `.avatar` | `<AppAvatar>` component |

## Meridian HR Role System

`BaseHRController::getHRRole()` maps Spatie roles to one of four strings: `admin`, `manager`, `employee-full`, `employee-basic`. It never returns the plain string `'employee'`.

**Priority order:** admin > manager > employee-full > employee-basic (default for any authenticated user).

When branching on role in Vue templates, always guard against all variants — never check `hrRole === 'employee'` because that will never match. Use `!['admin', 'manager'].includes(hrRole)` to target all employee variants. The same pattern is used in PHP: `!in_array($this->getHRRole(), ['admin', 'manager'])`.

The `hrRole` prop is passed to every Meridian HR page via `BaseHRController::getCommonProps()`. `MeridianLayout` uses it for sidebar navigation (separate nav structures for `employee-basic`, `employee-full`, `manager`, `admin`).

### Manager Access Control & Navigation

Manager navigation follows a **personal workspace + team oversight** pattern:

**Workspace** (personal items, same routes as employees):
- My leaves (`hr.leave-requests` with no scope parameter) — manager's own leave requests
- My timesheets (`hr.my-timesheets`) — manager's own timesheets

**Approvals** (action queues):
- Leaves (`hr.approvals.leave`) — pending leave requests requiring approval
- Timesheets (`hr.approvals.time`) — submitted timesheets requiring review

**Team** (read-only oversight):
- All leaves (`hr.leave-requests?scope=team`) — view all team leave requests (read-only)
- All timesheets (`hr.timesheet-talent?scope=team`) — view all team timesheets (read-only)

**Personal**:
- Documents, Emergency contact, My profile

#### Scope-Based Filtering

Controllers detect the `?scope=team` query parameter to distinguish between personal and team views:

**EmployeeLeaveRequestController:**
```php
$scope = request()->query('scope');
$showPersonalOnly = false;

if (!in_array($hrRole, ['admin'])) {
    if (!in_array($hrRole, ['manager'])) {
        $showPersonalOnly = true; // Employees always personal
    } elseif ($scope !== 'team') {
        $showPersonalOnly = true; // Managers personal unless scope=team
    }
}

if ($showPersonalOnly) {
    $currentEmployee = Employee::where('user_id', auth()->id())->first();
    $query->where('employee_id', $currentEmployee->id);
}
```

**TimesheetController::timesheetTalent():**
- Same scope detection logic
- Filters timesheets, leave days, and employee dropdown to current employee when `$showPersonalOnly = true`

#### Read-Only Enforcement in Team Views

Team views are strictly read-only for managers:

**LeaveRequest.vue:**
- Edit button: Hidden for managers (`v-if="hrRole !== 'manager'"`)
- Archive button: Hidden for managers
- View Details button: Visible for all roles

**TimesheetTalent.vue:**
- View/Edit button: Shows "View" with eye icon for managers (not "View / Edit")
- Status button: Hidden for managers (`v-if="hrRole === 'admin'"`)
- Delete button: Hidden for managers
- Save/Submit buttons in entry modal: Hidden for managers (`v-if="... && hrRole !== 'manager'"`)
- Day cells: Not clickable for managers (`canEdit()` returns `false` for `hrRole === 'manager'`)
- Cancel button text: Changes to "Close" for managers (read-only mode)

**Access Summary:**
| View Type | Manager Can | Manager Cannot |
|---|---|---|
| **My leaves/timesheets** | View, edit, submit own data | N/A |
| **Team leaves/timesheets** | View team data | Edit, delete, or modify team data |
| **Approval queues** | Approve, reject requests | N/A |

### Dashboard role views (`Dashboard.vue`)

Three template blocks share one page:
- **Employee** (`!['admin', 'manager'].includes(hrRole)`) — personal leave balances, next pay tile, upcoming own leaves, personal activity feed
- **Manager** (`hrRole === 'manager'`) — team pending approvals, team out today, approval queue
- **Admin** (`v-else`) — org headcount, on-leave breakdown, pending requests, dept chart, utilization chart

### Leave Balance Data

`LeaveBalanceService::getEmployeeBalanceSummary($employeeId, $eventId)` returns `EmployeeLeaveBalance` records with a `leaveType` relation. Map to dashboard shape by matching `strtolower($balance->leaveType->title)` containing `'annual'`, `'sick'`, or `'personal'`. The linked employee is found via `Employee::where('user_id', auth()->id())->first()`.

## Timesheet Approval Workflow

The system implements a **two-stage approval workflow** for timesheets:

### Stage 1: Manager/Admin Review
- **Status transition**: `Submitted` → `Pending Payroll` (on approval) or `Rejected`
- **Controller**: `TimesheetController::approveTimesheet()` / `rejectTimesheet()`
- **Routes**: `hr.approvals.time.approve` / `hr.approvals.time.reject`
- **Database fields updated**:
  - `status_id` = Pending Payroll status ID (or Rejected)
  - `performer_id` = Manager/admin user ID who approved
  - `additional_information` = Optional notes from manager
- **UI**: Manager Review tab in `TimesheetApprovals.vue` (visible to managers and admins)

### Stage 2: Payroll Review (Admin only)
- **Status transition**: `Pending Payroll` → `Approved` (final) or `Rejected`
- **Controller**: `TimesheetController::payrollApproveTimesheet()` / `payrollRejectTimesheet()`
- **Routes**: `hr.payroll.time.approve` / `hr.payroll.time.reject` (in `routes/hr/manager.php`)
- **Database fields updated**:
  - `status_id` = Approved status ID (or Rejected)
  - `payroll_approval_id` = Final status ID from payroll
  - `payroll_reviewed` = 1 (marks as reviewed by payroll)
  - `payroll_additional_information` = Optional notes from payroll
- **UI**: Payroll Review tab in `TimesheetApprovals.vue` (admin only)

### Timesheet Statuses

`EmployeeTimesheetStatus` model defines five statuses with helper methods:
- `Pending` - Initial state (draft)
- `Submitted` - Employee submitted, awaiting manager review
- `Pending Payroll` - Manager approved, awaiting payroll verification
- `Approved` - Final approval by payroll, ready for payment
- `Rejected` - Rejected by either manager or payroll

**Helper methods** (all return `?int`):
- `EmployeeTimesheetStatus::pendingId()`
- `EmployeeTimesheetStatus::submittedId()`
- `EmployeeTimesheetStatus::pendingPayrollId()`
- `EmployeeTimesheetStatus::approvedId()`
- `EmployeeTimesheetStatus::rejectedId()`

### TimesheetApprovals.vue

**Props**:
- `submittedTimesheets` - Array of timesheets in Submitted status (for manager review)
- `payrollTimesheets` - Array of timesheets in Pending Payroll status (for payroll review)
- `isAdmin` - Boolean, controls visibility of Payroll Review tab

**State**:
- `selectedManager` - Set of selected IDs for manager approval actions
- `selectedPayroll` - Set of selected IDs for payroll approval actions
- `activeTab` - 'manager' or 'payroll' (tab switching, admin only)

**Actions**:
- Manager approval → sends to `Pending Payroll` status
- Manager rejection → sets to `Rejected` status
- Payroll approval → sets to final `Approved` status
- Payroll rejection → sets to `Rejected` status (overrides manager approval)

### Data Flow

```
Employee submits → Submitted
                      ↓
Manager reviews → Pending Payroll (on approve)
                      ↓            ↘ Rejected (on reject)
Payroll reviews → Approved        
                      ↘ Rejected (if payroll rejects)
```

**Key principle**: If payroll rejects a timesheet, it is rejected even though the manager approved it. This allows payroll to catch discrepancies in hours, calculations, or payment eligibility.

### Timesheet Calculations and Leave Interactions

Timesheets track employee attendance and automatically calculate payment based on worked days, leave, and unpaid leave.

#### Day Action Codes

Each calendar day in a timesheet entry has a `day_action` value:
- **`W`** (Worked) - Normal working day, employee present
- **`L`** (Leave) - Paid leave day (approved leave request)
- **`U`** (Unpaid) - Unpaid leave or absence
- **`0`** (Non-working) - Weekend, holiday, or outside contract period

#### Leave Integration

When loading timesheet entries, the system:

1. **Fetches approved leave requests** for the employee and event:
   ```php
   EmployeeLeaveRequest::active()
       ->where('status_id', $approvedStatusId)
       ->where('employee_id', $employee->id)
       ->forEvent($eventId)
   ```

2. **Compiles leave dates** into a lookup array (`$leaveDays[$employeeId][$date]`)

3. **Auto-marks leave days** in timesheet with `isLeave` flag for UI display

4. **Employee can override** the day action manually (e.g., mark as `U` for unpaid or `W` if they worked)

#### Payment Calculation

On timesheet submission (`timesheetTalentSubmit`), the system calculates payment:

**Step 1: Count day actions**
```php
$countWorked = entries where day_action = 'W'
$countLeaves = entries where day_action = 'L'
$countUnpaid = entries where day_action = 'U'
```

**Step 2: Get salary and daily rate**
```php
$monthlySalary = EmployeeSalary::net_salary (latest effective)
$dailyRate = $monthlySalary / 30
```

**Step 3: Determine full month vs partial month**
```php
$isFullMonth = ($startDay <= 5 && $endDay >= $daysInMonth - 2)
```
- Full month: Employee worked substantially the entire month (contract covers most of the month)
- Partial month: Employee started/ended mid-month (contract assignment/release dates)

**Step 4: Calculate payment**

**Full Month:**
```php
$totalPayment = $monthlySalary - ($countUnpaid * $dailyRate)
$paidDays = 30 - $countUnpaid
```
- Base pay = full monthly salary
- Deduct only unpaid days
- Paid leave days don't reduce salary

**Partial Month:**
```php
$workedDays = $endDay - $startDay + 1  // Calendar days in range
$paidDays = max(0, $workedDays - $countUnpaid)
$totalPayment = $dailyRate * $paidDays
```
- Prorate based on actual calendar days in contract period
- Deduct unpaid days from the calendar range

**Step 5: Update timesheet record**
```php
'days_worked'                     => $countWorked,
'leave_taken'                     => $countLeaves,
'unpaid_leave_taken'              => $countUnpaid,
'total_days_eligible_for_payment' => $paidDays,
'salary'                          => $monthlySalary,
'daily_rate'                      => $dailyRate,
'total_payment'                   => $totalPayment,
'status_id'                       => Submitted
```

#### Key Payment Rules

1. **Paid leave is not deducted** from salary (full month) or calendar days (partial month)
2. **Unpaid leave always deducts** from payment
3. **Weekends/holidays** (action `0`) are not counted in any category
4. **Full month employees** get guaranteed monthly salary minus unpaid days only
5. **Partial month employees** are paid daily rate × eligible calendar days

#### Contract Period Windowing

Timesheets respect event assignment dates (`employee_events.assigned_at` / `released_at`):
- Days before `assigned_at` are excluded (action `0`)
- Days after `released_at` are excluded (action `0`)
- Only days within the active assignment period are eligible for entry

## Authentication

- Standard Laravel Breeze email/password flow (`routes/auth.php`)
- Microsoft SSO via Laravel Socialite (`MicrosoftController`)
- OTP middleware (`otp.pending`) sits between login and app access
- Spatie Permission guards roles/permissions; `hrRole` passed to pages is derived from `BaseHRController::getHRRole()` which checks Spatie roles in priority order
