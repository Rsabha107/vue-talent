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
     * This seeder sets up role-based access control for the HR system:
     * - employee-basic: Restricted access (profile, leave, timesheet, emergency)
     * - employee-full: Extended access (includes salary, banks, addresses, payslips, documents)
     * - manager: Approval workflows + employee-full permissions
     * - admin: Full system access
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Document permissions
            'view-documents' => 'View employee documents',
            'manage-documents' => 'Upload and delete documents',
            
            // Financial permissions
            'view-salary' => 'View salary information',
            'manage-salary' => 'Create and edit salary records',
            'view-banks' => 'View bank details',
            'manage-banks' => 'Create and edit bank details',
            'view-payslips' => 'View payslips',
            
            // Personal data permissions
            'view-addresses' => 'View address information',
            'manage-addresses' => 'Create and edit addresses',
            
            // Approval permissions
            'approve-leave' => 'Approve or reject leave requests',
            'approve-timesheet' => 'Approve or reject timesheets',
            
            // Management permissions
            'manage-employees' => 'Create, edit, and manage employees',
            'view-all-employees' => 'View all employee records',
            'manage-events' => 'Create and manage events',
            'manage-settings' => 'Manage system settings (leave types, venues, etc)',
            'manage-roles' => 'Manage roles and permissions',
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
        
        // Basic employees have no extra permissions
        // They can only access: dashboard, profile, leave, timesheet, emergency contacts
        $employeeBasic->syncPermissions([]);
        
        // =====================================================================
        // Full Employee Role - Extended Access
        // =====================================================================
        $employeeFull = Role::firstOrCreate(
            ['name' => 'employee-full'],
            ['guard_name' => 'web']
        );
        
        $employeeFull->syncPermissions([
            'view-documents',
            'view-salary',
            'view-banks',
            'view-addresses',
            'view-payslips',
        ]);
        
        // =====================================================================
        // Manager Role - Approvals + Extended Access
        // =====================================================================
        $manager = Role::firstOrCreate(
            ['name' => 'manager'],
            ['guard_name' => 'web']
        );
        
        $manager->syncPermissions([
            // Employee-full permissions
            'view-documents',
            'view-salary',
            'view-banks',
            'view-addresses',
            'view-payslips',
            
            // Manager-specific permissions
            'approve-leave',
            'approve-timesheet',
            'view-all-employees',
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
        $this->command->info('✓ employee-basic: Restricted access');
        $this->command->info('✓ employee-full: Extended access');
        $this->command->info('✓ manager: Approvals + extended access');
        $this->command->info('✓ admin: Full system access');
    }
}
