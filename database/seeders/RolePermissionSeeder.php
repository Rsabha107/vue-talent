<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder sets up permission-based access control for the modular HR system:
     * - employee-basic: Restricted access (profile, leave, timesheet, emergency)
     * - employee-full: Extended access (includes salary, banks, addresses, payslips, documents)
     * - manager: Team oversight + approval workflows
     * - payroll-admin: Payroll processing permissions
     * - admin: Full system access
     * 
     * Users can have multiple roles (e.g., employee + manager + payroll-admin)
     */
    public function run(): void
    {
        // =====================================================================
        // Define All Permissions
        // =====================================================================
        $permissions = [
            // ─────────────────────────────────────────────────────────────────
            // Employee Self-Service (everyone gets these via controller logic)
            // ─────────────────────────────────────────────────────────────────
            'view.own.leaves' => 'View own leave requests',
            'create.own.leaves' => 'Create own leave requests',
            'edit.own.leaves' => 'Edit own leave requests',
            'view.own.timesheets' => 'View own timesheets',
            'create.own.timesheets' => 'Create own timesheets',
            'edit.own.timesheets' => 'Edit own timesheets',
            
            // ─────────────────────────────────────────────────────────────────
            // Manager Permissions - Team Oversight
            // ─────────────────────────────────────────────────────────────────
            'view.team.leaves' => 'View team leave requests',
            'view.team.timesheets' => 'View team timesheets',
            'approve.leaves' => 'Approve or reject leave requests (stage 1)',
            'approve.timesheets' => 'Approve or reject timesheets (stage 1)',
            
            // ─────────────────────────────────────────────────────────────────
            // HR Admin Permissions
            // ─────────────────────────────────────────────────────────────────
            'manage.employees' => 'Create, edit, and manage employees',
            'manage.leave.types' => 'Manage leave types and eligibility',
            'manage.departments' => 'Manage departments and designations',
            'view.all.data' => 'View all system data (unrestricted)',
            
            // ─────────────────────────────────────────────────────────────────
            // Payroll Module Permissions
            // ─────────────────────────────────────────────────────────────────
            'payroll.access' => 'Access payroll module',
            'payroll.review.timesheets' => 'Review timesheets for payroll',
            'payroll.approve.timesheets' => 'Final timesheet approval (stage 2)',
            'payroll.reject.timesheets' => 'Reject timesheets from payroll',
            'payroll.process.payments' => 'Process payment batches',
            'payroll.generate.bank.files' => 'Generate bank payment files',
            'payroll.view.missing.timesheets' => 'View missing timesheet reports',
            
            // ─────────────────────────────────────────────────────────────────
            // Extended Employee Permissions (employee-full)
            // ─────────────────────────────────────────────────────────────────
            'view.documents' => 'View employee documents',
            'manage.documents' => 'Upload and delete documents',
            'view.salary' => 'View salary information',
            'manage.salary' => 'Create and edit salary records',
            'view.banks' => 'View bank details',
            'manage.banks' => 'Create and edit bank details',
            'view.payslips' => 'View payslips',
            'view.addresses' => 'View address information',
            'manage.addresses' => 'Create and edit addresses',
            
            // ─────────────────────────────────────────────────────────────────
            // Legacy Permissions (kept for backward compatibility)
            // ─────────────────────────────────────────────────────────────────
            'approve-leave' => 'Approve or reject leave requests (legacy)',
            'approve-timesheet' => 'Approve or reject timesheets (legacy)',
            'manage-employees' => 'Manage employees (legacy)',
            'view-all-employees' => 'View all employees (legacy)',
            'manage-events' => 'Create and manage events',
            'manage-settings' => 'Manage system settings',
            'manage-roles' => 'Manage roles and permissions',
            
            // ─────────────────────────────────────────────────────────────────
            // Future Module Permissions (placeholder)
            // ─────────────────────────────────────────────────────────────────
            'procurement.access' => 'Access procurement module',
            'procurement.create.orders' => 'Create purchase orders',
            'recruiting.access' => 'Access recruiting module',
            'recruiting.post.jobs' => 'Post job openings',
            'finance.access' => 'Access finance module',
            'finance.view.reports' => 'View financial reports',
        ];
        
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }
        
        // =====================================================================
        // Basic Employee Role - Restricted Access
        // =====================================================================
        $employeeBasic = Role::firstOrCreate(
            ['name' => 'employee-basic'],
            ['guard_name' => 'web']
        );
        
        // Basic employees have no extra permissions beyond self-service
        // (Self-service permissions are granted at controller level)
        $employeeBasic->syncPermissions([]);
        
        // =====================================================================
        // Full Employee Role - Extended Access
        // =====================================================================
        $employeeFull = Role::firstOrCreate(
            ['name' => 'employee-full'],
            ['guard_name' => 'web']
        );
        
        $employeeFull->syncPermissions([
            'view.documents',
            'view.salary',
            'view.banks',
            'view.addresses',
            'view.payslips',
            // Legacy permissions
            'view-documents',
            'view-salary',
            'view-banks',
            'view-addresses',
            'view-payslips',
        ]);
        
        // =====================================================================
        // Manager Role - Team Oversight + Approvals
        // =====================================================================
        $manager = Role::firstOrCreate(
            ['name' => 'manager'],
            ['guard_name' => 'web']
        );
        
        $manager->syncPermissions([
            // Manager-specific permissions
            'view.team.leaves',
            'view.team.timesheets',
            'approve.leaves',
            'approve.timesheets',
            // Legacy
            'approve-leave',
            'approve-timesheet',
            'view-all-employees',
        ]);
        
        // =====================================================================
        // Payroll Admin Role - Payroll Processing
        // =====================================================================
        $payrollAdmin = Role::firstOrCreate(
            ['name' => 'payroll-admin'],
            ['guard_name' => 'web']
        );
        
        $payrollAdmin->syncPermissions([
            'payroll.access',
            'payroll.review.timesheets',
            'payroll.approve.timesheets',
            'payroll.reject.timesheets',
            'payroll.process.payments',
            'payroll.generate.bank.files',
            'payroll.view.missing.timesheets',
        ]);
        
        // =====================================================================
        // Admin Role - Full System Access
        // =====================================================================
        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        
        // Admins get all permissions
        $admin->syncPermissions(Permission::all());
        
        // =====================================================================
        // Legacy Role Aliases (for backward compatibility)
        // =====================================================================
        
        // Map 'employee' role to 'employee-full'
        $employee = Role::firstOrCreate(
            ['name' => 'employee'],
            ['guard_name' => 'web']
        );
        $employee->syncPermissions($employeeFull->permissions);
        
        // Map 'administrator' and 'hr-admin' to 'admin'
        foreach (['administrator', 'hr-admin'] as $adminAlias) {
            $aliasRole = Role::firstOrCreate(
                ['name' => $adminAlias],
                ['guard_name' => 'web']
            );
            $aliasRole->syncPermissions(Permission::all());
        }
        
        // Map 'supervisor' to 'manager'
        $supervisor = Role::firstOrCreate(
            ['name' => 'supervisor'],
            ['guard_name' => 'web']
        );
        $supervisor->syncPermissions($manager->permissions);
        
        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('✓ employee-basic: Restricted access (self-service only)');
        $this->command->info('✓ employee-full: Extended access (documents, salary, banks, payslips)');
        $this->command->info('✓ manager: Team oversight + approvals (stage 1)');
        $this->command->info('✓ payroll-admin: Payroll processing + final approvals (stage 2)');
        $this->command->info('✓ admin: Full system access');
        $this->command->info('');
        $this->command->info('Note: Users can have multiple roles for combined permissions');
        $this->command->info('Example: employee + manager = personal workspace + team oversight');
    }
}
