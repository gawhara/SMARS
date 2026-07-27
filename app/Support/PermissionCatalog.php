<?php

namespace App\Support;

/**
 * The single source of truth for the RBAC permission vocabulary and the default
 * role → permission grants. Used by PermissionSeeder (to populate the tables)
 * and by AppServiceProvider (to register a Gate ability per permission), so the
 * gate list never depends on the database being migrated at boot time.
 *
 * Convention: "{module}.view" = read, "{module}.manage" = create/update/delete,
 * plus a few explicit high-risk abilities (employees.delete, devices.provision,
 * attendance.reconcile, payroll.manage).
 */
class PermissionCatalog
{
    /**
     * name => group.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // Organization structure
            'companies.view' => 'organization',
            'companies.manage' => 'organization',
            'branches.view' => 'organization',
            'branches.manage' => 'organization',
            'departments.view' => 'organization',
            'departments.manage' => 'organization',
            'positions.view' => 'organization',
            'positions.manage' => 'organization',
            'shifts.view' => 'organization',
            'shifts.manage' => 'organization',

            // Employees
            'employees.view' => 'employees',
            'employees.manage' => 'employees',
            'employees.delete' => 'employees',
            'documents.view' => 'employees',

            // Attendance
            'attendance.view' => 'attendance',
            'attendance.manage' => 'attendance',
            'attendance.reconcile' => 'attendance',

            // Biometric devices
            'devices.view' => 'devices',
            'devices.manage' => 'devices',
            'devices.provision' => 'devices',

            // Leaves
            'leaves.view' => 'leaves',
            'leaves.manage' => 'leaves',

            // Payroll
            'payroll.view' => 'payroll',
            'payroll.manage' => 'payroll',
            'penalties.view' => 'payroll',
            'penalties.manage' => 'payroll',

            // Cross-cutting
            'reports.view' => 'reports',
            'users.view' => 'system',
            'users.manage' => 'system',
            'audit.view' => 'system',
            'settings.view' => 'system',
            'settings.manage' => 'system',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * Default grants per role. super_admin is intentionally omitted — it bypasses
     * every check via Gate::before — but is still granted everything in the seeder
     * for completeness.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleDefaults(): array
    {
        $names = self::names();

        return [
            'super_admin' => $names,

            'hr_manager' => [
                'companies.view', 'companies.manage', 'branches.view', 'branches.manage',
                'departments.view', 'departments.manage', 'positions.view', 'positions.manage',
                'shifts.view', 'shifts.manage',
                'employees.view', 'employees.manage', 'employees.delete', 'documents.view',
                'attendance.view', 'attendance.manage', 'attendance.reconcile',
                'devices.view', 'devices.manage', 'devices.provision',
                'leaves.view', 'leaves.manage',
                'payroll.view', 'payroll.manage',
                'penalties.view', 'penalties.manage',
                'reports.view',
            ],

            'hr_officer' => [
                'companies.view', 'branches.view', 'departments.view', 'positions.view', 'shifts.view',
                'employees.view', 'employees.manage', 'documents.view',
                'attendance.view', 'attendance.manage',
                'devices.view',
                'leaves.view', 'leaves.manage',
                'penalties.view', 'penalties.manage',
                'reports.view',
            ],

            'accountant' => [
                'companies.view',
                'employees.view',
                'attendance.view',
                'payroll.view', 'payroll.manage',
                'penalties.view',
                'reports.view',
            ],

            'branch_manager' => [
                'companies.view', 'branches.view', 'departments.view', 'positions.view',
                'employees.view',
                'attendance.view', 'attendance.manage',
                'leaves.view', 'leaves.manage',
                'devices.view',
                'reports.view',
            ],

            'employee_viewer' => [
                'employees.view',
                'attendance.view',
                'reports.view',
            ],
        ];
    }
}
