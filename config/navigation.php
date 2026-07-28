<?php

/*
|--------------------------------------------------------------------------
| SMARS sidebar navigation
|--------------------------------------------------------------------------
|
| Single source of truth for the sidebar. Each item supports:
|   title      => translation key under app.* (rendered via __())
|   route      => named route for a direct link, or null for a group/placeholder
|   icon       => key resolved by resources/views/partials/icon.blade.php
|   permission => permission key (reserved for RBAC; not yet enforced)
|   active     => optional explicit route pattern for highlighting
|   children   => nested submenu items (makes the parent collapsible)
|
| A parent with `children` renders as a collapsible group. An item with a
| `route` renders as a link. An item with neither is a disabled placeholder
| for a module scheduled in a later phase.
|
*/

return [
    'items' => [
        [
            'title' => 'dashboard',
            'route' => 'dashboard',
            'icon' => 'dashboard',
            'permission' => null,
        ],

        // Organization structure — collapsible group (CODEX §8-9).
        [
            'title' => 'companies',
            'route' => null,
            'icon' => 'building',
            'permission' => 'companies.view',
            'children' => [
                ['title' => 'companies_list', 'route' => 'companies.index', 'permission' => 'companies.view'],
                ['title' => 'branches', 'route' => 'branches.index', 'permission' => 'branches.view'],
                ['title' => 'departments', 'route' => 'departments.index', 'permission' => 'departments.view'],
                ['title' => 'positions', 'route' => 'positions.index', 'permission' => 'positions.view'],
                ['title' => 'shifts', 'route' => 'shifts.index', 'permission' => 'shifts.view'],
            ],
        ],

        // Employees — collapsible group listing the four companies (children
        // resolved dynamically in App\Support\Navigation).
        [
            'title' => 'employees',
            'route' => null,
            'icon' => 'users',
            'permission' => 'employees.view',
            'children_source' => 'employees_by_company',
        ],

        // Modules scheduled for later phases (disabled placeholders).
        ['title' => 'employee_documents', 'route' => null, 'icon' => 'document', 'permission' => 'documents.view'],
        // Attendance — collapsible group. Flat sub-routes share the `attendance.`
        // prefix, so each needs an explicit `active` pattern to avoid over-matching.
        [
            'title' => 'attendance',
            'route' => null,
            'icon' => 'clock',
            'permission' => 'attendance.view',
            'children' => [
                ['title' => 'att.punch_log', 'route' => 'attendance.index', 'active' => 'attendance.index', 'permission' => 'attendance.view'],
                ['title' => 'att.daily_title', 'route' => 'attendance.daily', 'active' => 'attendance.daily', 'permission' => 'attendance.view'],
                ['title' => 'att.exceptions_title', 'route' => 'attendance.exceptions', 'active' => 'attendance.exceptions', 'permission' => 'attendance.view'],
                ['title' => 'recon.title', 'route' => 'attendance.reconciliation.index', 'active' => 'attendance.reconciliation.*', 'permission' => 'attendance.reconcile'],
                ['title' => 'att.matrix_title', 'route' => 'attendance.matrix', 'active' => 'attendance.matrix', 'permission' => 'attendance.view'],
                ['title' => 'att.report_title', 'route' => 'attendance.report', 'active' => 'attendance.report', 'permission' => 'attendance.view'],
                ['title' => 'att.corrections', 'route' => 'attendance.corrections.index', 'permission' => 'attendance.view'],
                ['title' => 'att.holidays', 'route' => 'attendance.holidays.index', 'permission' => 'attendance.manage'],
                ['title' => 'att.policies', 'route' => 'attendance.policies.index', 'permission' => 'attendance.manage'],
            ],
        ],
        ['title' => 'biometric_devices', 'route' => 'devices.index', 'icon' => 'fingerprint', 'permission' => 'devices.view'],
        ['title' => 'leaves', 'route' => 'attendance.leaves.index', 'icon' => 'calendar', 'permission' => 'leaves.view', 'active' => 'attendance.leaves.*'],
        [
            'title' => 'payroll',
            'route' => null,
            'icon' => 'wallet',
            'permission' => 'payroll.view',
            'children' => [
                ['title' => 'deduct.periods', 'route' => 'payroll.periods.index', 'active' => 'payroll.periods.*', 'permission' => 'payroll.view'],
                ['title' => 'deduct.title', 'route' => 'payroll.deductions.index', 'active' => 'payroll.deductions.*', 'permission' => 'payroll.view'],
                ['title' => 'latency.calculator', 'route' => 'latency.calculator', 'active' => 'latency.calculator', 'permission' => 'payroll.view'],
                ['title' => 'latency.policies', 'route' => 'latency.policies.index', 'active' => 'latency.policies.*', 'permission' => 'payroll.view'],
                ['title' => 'penalty.title', 'route' => 'penalties.index', 'active' => 'penalties.*', 'permission' => 'penalties.view'],
            ],
        ],
        ['title' => 'reports', 'route' => null, 'icon' => 'chart', 'permission' => 'reports.view'],
        ['title' => 'users_roles', 'route' => null, 'icon' => 'shield', 'permission' => 'users.view'],
        ['title' => 'audit_logs', 'route' => 'audit.logs.index', 'icon' => 'history', 'permission' => 'audit.view'],
        ['title' => 'settings', 'route' => null, 'icon' => 'settings', 'permission' => 'settings.view'],
    ],
];
