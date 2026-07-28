<?php

use App\Http\Controllers\AdministrativePenaltyController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AttendancePolicyController;
use App\Http\Controllers\AttendanceReconciliationController;
use App\Http\Controllers\AttendanceHolidayController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\BiometricProvisioningController;
use App\Http\Controllers\DeviceEnrollmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LatencyCalculatorController;
use App\Http\Controllers\LatencyPolicyController;
use App\Http\Controllers\PayrollDeductionController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/language/{locale}', LanguageController::class)->name('language.switch');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ---- Organization structure (view to read, manage to write) ----
    // Manage resources are registered before view resources so literal routes
    // (e.g. companies/create) win over wildcard show routes (companies/{id}).
    Route::middleware('can:companies.manage')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('can:companies.view')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['index', 'show']);
    });

    foreach ([
        'branches' => BranchController::class,
        'departments' => DepartmentController::class,
        'positions' => PositionController::class,
        'shifts' => ShiftController::class,
    ] as $name => $controller) {
        Route::middleware("can:{$name}.manage")->group(function () use ($name, $controller) {
            Route::resource($name, $controller)->only(['create', 'store', 'edit', 'update', 'destroy']);
        });
        Route::middleware("can:{$name}.view")->group(function () use ($name, $controller) {
            Route::resource($name, $controller)->only(['index']);
        });
    }

    // ---- Employees (delete is a distinct, higher-risk ability) ----
    Route::middleware('can:employees.manage')->group(function () {
        Route::resource('employees', EmployeeController::class)->only(['create', 'store', 'edit', 'update']);
    });
    Route::middleware('can:employees.delete')->group(function () {
        Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });
    Route::middleware('can:employees.view')->group(function () {
        Route::resource('employees', EmployeeController::class)->only(['index', 'show']);
    });

    // ---- Biometric devices ----
    // Provisioning writes user identity + fingerprint templates to real hardware.
    Route::middleware('can:devices.provision')->group(function () {
        Route::get('devices/{device}/provision', [BiometricProvisioningController::class, 'index'])->name('devices.provision');
        Route::post('devices/{device}/provision/copy', [BiometricProvisioningController::class, 'copy'])->name('devices.provision.copy');
        Route::post('devices/{device}/provision/move', [BiometricProvisioningController::class, 'move'])->name('devices.provision.move');
        Route::post('devices/{device}/provision/delete', [BiometricProvisioningController::class, 'destroy'])->name('devices.provision.delete');
    });
    Route::middleware('can:devices.manage')->group(function () {
        Route::post('devices/sync-all', [BiometricDeviceController::class, 'syncAll'])->name('devices.sync-all');
        Route::post('devices/{device}/test', [BiometricDeviceController::class, 'testConnection'])->name('devices.test');
        Route::post('devices/{device}/sync', [BiometricDeviceController::class, 'sync'])->name('devices.sync');
        Route::get('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'index'])->name('devices.enrollments.index');
        Route::post('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'store'])->name('devices.enrollments.store');
        Route::delete('devices/{device}/enrollments/{enrollment}', [DeviceEnrollmentController::class, 'destroy'])->name('devices.enrollments.destroy');
        Route::post('devices/{device}/enrollments/copy', [DeviceEnrollmentController::class, 'copy'])->name('devices.enrollments.copy');
        Route::resource('devices', BiometricDeviceController::class)->parameter('devices', 'device')->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('can:devices.view')->group(function () {
        Route::get('devices/{device}/punches', [BiometricDeviceController::class, 'punches'])->name('devices.punches');
        Route::resource('devices', BiometricDeviceController::class)->parameter('devices', 'device')->only(['index', 'show']);
    });

    // ---- Attendance ----
    Route::middleware('can:attendance.view')->group(function () {
        Route::get('attendance/employee/{employee}/print', [AttendanceController::class, 'printReport'])->name('attendance.employee.print');
        Route::get('attendance/employee/{employee}', [AttendanceController::class, 'employee'])->name('attendance.employee');
        Route::get('attendance/matrix', [AttendanceController::class, 'matrix'])->name('attendance.matrix');
        Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('attendance/exceptions', [AttendanceController::class, 'exceptions'])->name('attendance.exceptions');
        Route::get('attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
        Route::get('attendance/corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance.corrections.index');
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });
    Route::middleware('can:attendance.manage')->group(function () {
        Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('attendance/corrections/create', [AttendanceCorrectionController::class, 'create'])->name('attendance.corrections.create');
        Route::post('attendance/corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance.corrections.store');
        Route::get('attendance/policies', [AttendancePolicyController::class, 'index'])->name('attendance.policies.index');
        Route::get('attendance/policies/{company}/edit', [AttendancePolicyController::class, 'edit'])->name('attendance.policies.edit');
        Route::put('attendance/policies/{company}', [AttendancePolicyController::class, 'update'])->name('attendance.policies.update');
        Route::resource('attendance/holidays', AttendanceHolidayController::class)->except(['show'])->names('attendance.holidays');
        Route::get('attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import.form');
        Route::post('attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
    });
    Route::middleware('can:attendance.reconcile')->group(function () {
        Route::get('attendance/reconciliation', [AttendanceReconciliationController::class, 'index'])->name('attendance.reconciliation.index');
        Route::put('attendance/reconciliation/approve', [AttendanceReconciliationController::class, 'approve'])->name('attendance.reconciliation.approve');
        Route::put('attendance/reconciliation/reopen', [AttendanceReconciliationController::class, 'reopen'])->name('attendance.reconciliation.reopen');
        Route::put('attendance/corrections/{correction}/approve', [AttendanceCorrectionController::class, 'approve'])->name('attendance.corrections.approve');
        Route::put('attendance/corrections/{correction}/reject', [AttendanceCorrectionController::class, 'reject'])->name('attendance.corrections.reject');
    });

    // ---- Leaves ----
    Route::middleware('can:leaves.view')->group(function () {
        Route::get('attendance/leaves', [EmployeeLeaveController::class, 'index'])->name('attendance.leaves.index');
    });
    Route::middleware('can:leaves.manage')->group(function () {
        Route::get('attendance/leaves/create', [EmployeeLeaveController::class, 'create'])->name('attendance.leaves.create');
        Route::post('attendance/leaves', [EmployeeLeaveController::class, 'store'])->name('attendance.leaves.store');
        Route::put('attendance/leaves/{leave}/approve', [EmployeeLeaveController::class, 'approve'])->name('attendance.leaves.approve');
        Route::put('attendance/leaves/{leave}/reject', [EmployeeLeaveController::class, 'reject'])->name('attendance.leaves.reject');
    });

    // ---- Audit log ----
    Route::middleware('can:audit.view')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.logs.index');
    });

    // ---- Payroll periods + attendance deductions ----
    Route::middleware('can:payroll.view')->group(function () {
        Route::get('payroll/periods', [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
        Route::get('payroll/deductions', [PayrollDeductionController::class, 'index'])->name('payroll.deductions.index');
        Route::get('payroll/deductions/{employee}', [PayrollDeductionController::class, 'employee'])->name('payroll.deductions.employee');
        Route::get('latency/calculator', [LatencyCalculatorController::class, 'index'])->name('latency.calculator');
        Route::get('latency/policies', [LatencyPolicyController::class, 'index'])->name('latency.policies.index');
    });

    // ---- Latency (late-entry) policy management ----
    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('latency/policies', [LatencyPolicyController::class, 'store'])->name('latency.policies.store');
        Route::put('latency/policies/{latencyPolicy}', [LatencyPolicyController::class, 'update'])->name('latency.policies.update');
        Route::delete('latency/policies/{latencyPolicy}', [LatencyPolicyController::class, 'destroy'])->name('latency.policies.destroy');
    });

    // ---- Administrative penalties ----
    Route::middleware('can:penalties.view')->group(function () {
        Route::get('penalties', [AdministrativePenaltyController::class, 'index'])->name('penalties.index');
    });
    Route::middleware('can:penalties.manage')->group(function () {
        Route::post('penalties', [AdministrativePenaltyController::class, 'store'])->name('penalties.store');
        Route::put('penalties/{penalty}/cancel', [AdministrativePenaltyController::class, 'cancel'])->name('penalties.cancel');
    });
    Route::middleware('can:payroll.manage')->group(function () {
        Route::post('payroll/periods', [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
        Route::put('payroll/periods/{period}/lock', [PayrollPeriodController::class, 'lock'])->name('payroll.periods.lock');
        Route::put('payroll/periods/{period}/unlock', [PayrollPeriodController::class, 'unlock'])->name('payroll.periods.unlock');
        Route::get('payroll/periods/{period}/export', [PayrollPeriodController::class, 'export'])->name('payroll.periods.export');
    });
});
