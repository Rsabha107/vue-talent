# Role-Based Access Control Implementation Guide

## 🎯 Overview

This guide explains the new role-based access control system that adds granular employee permissions while maintaining backward compatibility.

## 📋 New Roles

| Role | Access Level | Features |
|------|-------------|----------|
| `employee-basic` | **Restricted** | Dashboard, Profile, Leave, Timesheet, Emergency Contacts |
| `employee-full` | **Extended** | Basic + Addresses, Documents, Payslips |
| `employee` (legacy) | **Extended** | Mapped to `employee-full` for backward compatibility |
| `manager` | **Approval** | Extended + Leave/Timesheet approvals, View team |
| `admin` | **Full Access** | All features + Employee management + Settings |

## 🔐 Permissions System

### Core Permissions

**Document Permissions:**
- `view-documents` - View and download documents (optional per employee)
- `manage-documents` - Upload and delete documents (admin only)

**Financial Permissions:**
- `view-salary` - View salary information
- `view-banks` - View bank details
- `view-payslips` - View payslips

**Personal Data:**
- `view-addresses` - View and manage addresses

**Management:**
- `approve-leave` - Approve/reject leave requests
- `approve-timesheet` - Approve/reject timesheets
- `manage-employees` - Full employee CRUD
- `manage-settings` - System configuration

## 🚀 Implementation Steps

### 1. Run Database Seeder

```bash
# Seed roles and permissions
php artisan db:seed --class=RolePermissionSeeder

# Verify roles were created
php artisan tinker
>>> \Spatie\Permission\Models\Role::pluck('name')
```

**Expected Output:**
```
[
  "employee-basic",
  "employee-full",
  "manager",
  "admin",
  "employee",      // Legacy alias
  "administrator", // Legacy alias
  "hr-admin",      // Legacy alias
  "supervisor"     // Legacy alias
]
```

### 2. Assign Roles to Users

**Option A: In Tinker (for testing)**
```php
php artisan tinker

// Assign employee-basic role
$user = \App\Models\User::find(1);
$user->assignRole('employee-basic');

// Assign employee-full role
$user = \App\Models\User::find(2);
$user->assignRole('employee-full');

// Assign manager role
$user = \App\Models\User::find(3);
$user->assignRole('manager');

// Assign admin role
$user = \App\Models\User::find(4);
$user->assignRole('admin');
```

**Option B: Create Migration/Seeder**
```php
// database/seeders/AssignDefaultRolesSeeder.php
public function run()
{
    // Assign all existing users to employee-full by default
    \App\Models\User::whereDoesntHave('roles')->each(function ($user) {
        $user->assignRole('employee-full');
    });
    
    // Or migrate existing 'employee' role users
    \App\Models\User::role('employee')->each(function ($user) {
        $user->syncRoles(['employee-full']); // Replace with employee-full
    });
}
```

**Option C: UI for Admin (Recommended)**
Create an admin interface to assign roles per user.

### 3. Grant Optional Document Access

Documents are **optional** and controlled by the `view-documents` permission:

```php
// Grant document access to specific employee
$user->givePermissionTo('view-documents');

// Revoke document access
$user->revokePermissionTo('view-documents');

// Check if user can view documents
if ($user->hasPermissionTo('view-documents')) {
    // Show documents menu
}
```

### 4. Clear Caches

```bash
# Clear permission cache
php artisan permission:cache-reset

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear application cache
php artisan cache:clear
```

## 🧪 Testing

### Test Different Role Access

**1. Test employee-basic (Restricted):**
```php
$user = User::find(1);
$user->assignRole('employee-basic');

// Login as this user
// Should see: Dashboard, Leave, Timesheet, Emergency, Profile
// Should NOT see: Addresses, Banks, Salary, Documents, Payslips
```

**2. Test employee-full (Extended):**
```php
$user = User::find(2);
$user->assignRole('employee-full');

// Login as this user
// Should see: Basic + Addresses, Documents, Payslips
```

**3. Test Optional Documents:**
```php
$basicEmployee = User::role('employee-basic')->first();

// Without permission - no documents menu
$basicEmployee->revokePermissionTo('view-documents');

// With permission - documents menu appears
$basicEmployee->givePermissionTo('view-documents');
```

### Verify Route Protection

```bash
# Test protected routes
curl -X GET http://localhost/hr/addresses
# Should return 403 for employee-basic without permission

# Test basic routes
curl -X GET http://localhost/hr/dashboard
# Should work for all authenticated users
```

## 📊 Navigation Changes

The sidebar navigation automatically adapts based on role:

### employee-basic Navigation:
```
Workspace
  - Home
  - Time off
  - Timesheet
Personal
  - Emergency Contact
  - My profile
```

### employee-full Navigation:
```
Workspace
  - Home
  - Time off
  - Timesheet
Personal
  - Addresses
  - Emergency Contact
Records
  - Documents (if permission granted)
  - Payslips
  - My profile
```

### manager Navigation:
```
Workspace
  - Home
  - Time off
  - Timesheet
Approvals
  - Leave requests (badge)
  - Timesheets (badge)
Personal
  - Addresses
  - Emergency Contact
Records
  - Documents
  - Payslips
  - Team
  - My profile
```

## 🔄 Migration from Old System

### If users currently have 'employee' role:

**Option 1: Keep as employee-full (Recommended)**
```php
// Run this migration
User::role('employee')->each(function ($user) {
    $user->syncRoles(['employee-full']);
});
```

**Option 2: Downgrade to employee-basic**
```php
// Only if you want to restrict existing employees
User::role('employee')->each(function ($user) {
    $user->syncRoles(['employee-basic']);
});
```

## 🛠️ Helper Methods

The `BaseHRController` provides helper methods:

```php
// In any controller extending BaseHRController

// Get current user's role
$role = $this->getHRRole();
// Returns: 'employee-basic', 'employee-full', 'manager', or 'admin'

// Check if user has extended access
if ($this->hasExtendedAccess()) {
    // User is employee-full, manager, or admin
}

// Check if user is manager or admin
if ($this->isManagerOrAdmin()) {
    // User can approve requests
}
```

## 🔍 Checking Permissions in Blade/Vue

**In Controllers:**
```php
if (auth()->user()->hasPermissionTo('view-documents')) {
    // Show documents
}
```

**In Blade Templates:**
```blade
@can('view-documents')
    <a href="{{ route('hr.documents') }}">Documents</a>
@endcan
```

**In Vue Components:**
```javascript
// Check via props
const canViewDocuments = computed(() => {
  return props.auth.user?.permissions?.includes('view-documents')
})
```

## 📝 Best Practices

1. **Default to employee-basic** for new users, upgrade as needed
2. **Use permissions for optional features** (like documents)
3. **Grant roles, not individual permissions** when possible
4. **Test with different roles** before deploying
5. **Document which features require which permissions**
6. **Clear caches** after role/permission changes
7. **Use middleware** for route protection instead of controller checks

## 🐛 Troubleshooting

### "403 Forbidden" on routes that should be accessible

**Solution:**
```bash
# Clear permission cache
php artisan permission:cache-reset

# Verify user has correct role
php artisan tinker
>>> $user = User::find(1);
>>> $user->roles->pluck('name');
>>> $user->permissions->pluck('name');
```

### Navigation items not showing/hiding correctly

**Solution:**
1. Check `hrRole` prop is being passed correctly
2. Verify `getHRRole()` in BaseHRController returns expected value
3. Clear browser cache
4. Check browser console for Vue errors

### Documents menu not appearing for basic employees with permission

**Solution:**
```bash
# Ensure permission exists
php artisan tinker
>>> Permission::where('name', 'view-documents')->exists();

# Grant permission
>>> $user->givePermissionTo('view-documents');

# Clear cache
>>> app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

## 📚 Additional Resources

- [Spatie Laravel Permission Docs](https://spatie.be/docs/laravel-permission)
- `routes/hr/employee.php` - Route definitions with middleware
- `app/Http/Controllers/MeridianHR/BaseHRController.php` - Role helper methods
- `resources/js/Layouts/MeridianLayout.vue` - Navigation structure

## ✅ Verification Checklist

- [ ] Roles seeded successfully
- [ ] Users assigned appropriate roles
- [ ] Route middleware working (403 on restricted routes)
- [ ] Navigation adapts to different roles
- [ ] Optional documents permission working
- [ ] Backward compatibility maintained (legacy 'employee' role)
- [ ] All caches cleared
- [ ] Tested with each role type
- [ ] Admin can still access all features
- [ ] Manager can approve requests
- [ ] Basic employees restricted appropriately

---

**Need Help?** Check the role and permission tables:
```sql
SELECT * FROM roles;
SELECT * FROM permissions;
SELECT * FROM model_has_roles;
SELECT * FROM model_has_permissions;
SELECT * FROM role_has_permissions;
```
