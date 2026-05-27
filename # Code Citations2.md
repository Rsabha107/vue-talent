# Code Citations

## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```


## License: unknown
https://github.com/ihzaa/Laravel-Starter-Project/blob/1b851aaf88f3bcc20f50a66fa5df98426cc18ab4/app/Http/Kernel.php

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update
```


## License: MIT
https://github.com/spatie/laravel-permission/blob/536d41d0c82e8fdbeeb2917ef9225b8059d1d96b/docs/basic-usage/middleware.md

```
Great question! Here's the best approach to add restricted employee access while keeping your structure intact:

## 🎯 Recommended Approach

### 1. **Use Spatie Permission Roles** (Already Installed)

Create granular roles in your database:
- `employee-basic` - Restricted access (profile, leave, timesheet, emergency)
- `employee-full` - Current employee access (includes addresses, banks, salary, payslips, documents)
- `manager` - Approval workflows
- `admin` - Full system access

### 2. **Add Middleware to Route Groups**

Keep your current file structure but add middleware:

**routes/hr/employee.php** - Split into two sections:
```php
// Basic Employee Routes (everyone)
Route::middleware(['auth', 'role:employee-basic|employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
});

// Extended Employee Routes (full access + managers + admins)
Route::middleware(['auth', 'role:employee-full|manager|admin'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});

// Optional Documents (configurable per employee)
Route::middleware(['auth', 'permission:view-documents'])
    ->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
});
```

### 3. **Update Middleware Aliases**

**app/Http/Kernel.php** or **bootstrap/app.php** (Laravel 11+):
```php
protected $middlewareAliases = [
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
];
```

### 4. **Update Navigation
```

