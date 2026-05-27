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

### Dashboard role views (`Dashboard.vue`)

Three template blocks share one page:
- **Employee** (`!['admin', 'manager'].includes(hrRole)`) — personal leave balances, next pay tile, upcoming own leaves, personal activity feed
- **Manager** (`hrRole === 'manager'`) — team pending approvals, team out today, approval queue
- **Admin** (`v-else`) — org headcount, on-leave breakdown, pending requests, dept chart, utilization chart

### Leave Balance Data

`LeaveBalanceService::getEmployeeBalanceSummary($employeeId, $eventId)` returns `EmployeeLeaveBalance` records with a `leaveType` relation. Map to dashboard shape by matching `strtolower($balance->leaveType->title)` containing `'annual'`, `'sick'`, or `'personal'`. The linked employee is found via `Employee::where('user_id', auth()->id())->first()`.

## Authentication

- Standard Laravel Breeze email/password flow (`routes/auth.php`)
- Microsoft SSO via Laravel Socialite (`MicrosoftController`)
- OTP middleware (`otp.pending`) sits between login and app access
- Spatie Permission guards roles/permissions; `hrRole` passed to pages is derived from `BaseHRController::getHRRole()` which checks Spatie roles in priority order
