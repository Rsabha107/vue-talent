# Routes Structure

## Overview

Routes are organized into modular files by access level and purpose, making the codebase easier to maintain and scale.

```
routes/
├── web.php              # Core app routes & module loader
├── auth.php             # Authentication (login, register, password reset)
├── hr/                  # Meridian HR module routes
│   ├── employee.php     # Employee-facing routes (all authenticated users)
│   ├── manager.php      # Manager approval workflows
│   └── admin.php        # Admin management & settings
└── legacy/              # Legacy systems
    └── ems.php          # Old event management system (Bootstrap UI)
```

## Route Files

### `web.php` - Main Entry Point
- Guest routes (login, register, password reset)
- OTP verification
- Microsoft SSO
- Home redirect
- Event session management (global)
- User profile management
- Loads modular route files

### `auth.php` - Authentication
- Login/Logout
- Registration
- Password reset
- Email verification
- Two-factor authentication

### `hr/employee.php` - Employee Routes
**Access Level:** All authenticated users  
**Middleware:** `auth`

**Features:**
- Dashboard & Profile
- Personal leave requests
- Personal timesheet
- View own documents
- View payslips
- Manage personal data (addresses, emergency contacts)

**Route Examples:**
- `GET /hr/dashboard`
- `GET /hr/leave`
- `POST /hr/timesheet/submit`
- `GET /hr/documents`

### `hr/manager.php` - Manager Routes
**Access Level:** Managers & Admins  
**Middleware:** `auth` (role-based access control in controllers)

**Features:**
- Leave approvals
- Timesheet approvals

**Route Examples:**
- `GET /hr/approvals/leave`
- `POST /hr/approvals/leave/approve`
- `POST /hr/approvals/time/reject`

### `hr/admin.php` - Admin Routes
**Access Level:** Admins only  
**Middleware:** `auth` (role-based access control in controllers)

**Features:**
- Employee management (CRUD)
- Employee import/export
- Event management
- Event-employee assignments
- Event templates
- Leave type settings
- Venue settings
- Document management (upload/delete)
- Document category management
- Salary & bank management (all employees)
- Timesheet management (all employees)

**Route Examples:**
- `GET /hr/employee`
- `GET /hr/master-employee`
- `POST /hr/employee/import`
- `GET /hr/events`
- `GET /hr/leave-types`

### `legacy/ems.php` - Legacy Routes
**Access Level:** Authenticated users  
**Middleware:** `auth`

**Legacy Systems:**
- User management (old UI)
- Role & permission management (old UI)
- Event management (old UI with Bootstrap tables)
- Venue management (old UI)
- Functional areas (old UI)

**Route Examples:**
- `GET /users`
- `GET /api/users` (Bootstrap Table data endpoint)
- `GET /events` (legacy, conflicts with HR events)
- `GET /roles-permissions`

**⚠️ Migration Note:** These routes use the old Bootstrap UI and Bootstrap Table AJAX patterns. Consider migrating to Meridian HR equivalents.

## Route Naming Convention

All HR routes use the `hr.` prefix:
- `hr.dashboard`
- `hr.employee`
- `hr.approvals.leave`

Legacy routes use their original names:
- `users.index`
- `events.index` (legacy)
- `roles.index`

## Access Control

Currently, access control is handled in two ways:

1. **Middleware** - All routes require `auth` middleware
2. **Controller-level** - Controllers check `hrRole` from `BaseHRController::getHRRole()`

### Recommended Improvements

Add role-based middleware to route groups:

```php
// In app/Http/Kernel.php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
];

// In routes/hr/admin.php
Route::middleware(['auth', 'role:admin'])->prefix('hr')->name('hr.')->group(function () {
    // Admin routes
});
```

## Duplicate Route Resolution

### Fixed: `/hr/documents`
Previously defined in both:
1. Line 172: `Route::get('/documents', [EmployeeController::class, 'documents'])`
2. Line 216: `Route::get('/documents', [DocumentController::class, 'index'])`

**Resolution:** Removed from line 172, kept in `DocumentController::index()` which handles both employee and admin views.

### Legacy Conflicts
Some legacy routes may conflict with HR routes:
- `/events` (legacy) vs `/hr/events` (HR) - Different endpoints, no conflict
- `/venues` (legacy) vs `/hr/venues` (HR) - Different endpoints, no conflict

## Benefits of This Structure

✅ **Organized by Access Level** - Easy to find routes by user role  
✅ **Smaller Files** - Each file is focused and maintainable  
✅ **Clear Security Model** - Access control visible at file level  
✅ **Easy to Extend** - Add new modules without cluttering main file  
✅ **Reduced Merge Conflicts** - Team members work on different files  
✅ **Self-Documenting** - File names indicate purpose and access level  

## Migration Path

To migrate from legacy to Meridian HR:

1. **Identify legacy routes** in `routes/legacy/ems.php`
2. **Find equivalent HR route** in `routes/hr/`
3. **Update frontend references** to use new route names
4. **Test thoroughly** with different user roles
5. **Remove legacy route** once migration is complete
6. **Update documentation**

## Testing Routes

```bash
# List all routes
php artisan route:list

# Filter by prefix
php artisan route:list --path=hr

# Filter by name
php artisan route:list --name=hr.

# Show only specific columns
php artisan route:list --columns=method,uri,name,action
```

## Next Steps

1. **Add role middleware** to admin and manager route groups
2. **Migrate legacy routes** to Meridian HR equivalents
3. **Add API versioning** if building mobile apps
4. **Document route parameters** in controller docblocks
5. **Add route caching** in production: `php artisan route:cache`
