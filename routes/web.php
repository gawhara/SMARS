<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendancePolicyController;
use App\Http\Controllers\AttendanceHolidayController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\DeviceEnrollmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LanguageController;
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
    Route::resource('companies', CompanyController::class);
    Route::resource('branches', BranchController::class)->except(['show']);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('positions', PositionController::class)->except(['show']);
    Route::resource('shifts', ShiftController::class)->except(['show']);
    Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])
        ->name('employees.restore');
    Route::resource('employees', EmployeeController::class);
    Route::post('devices/{device}/test', [BiometricDeviceController::class, 'testConnection'])->name('devices.test');
    Route::post('devices/{device}/sync', [BiometricDeviceController::class, 'sync'])->name('devices.sync');
    Route::get('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'index'])->name('devices.enrollments.index');
    Route::post('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'store'])->name('devices.enrollments.store');
    Route::delete('devices/{device}/enrollments/{enrollment}', [DeviceEnrollmentController::class, 'destroy'])->name('devices.enrollments.destroy');
    Route::post('devices/{device}/enrollments/copy', [DeviceEnrollmentController::class, 'copy'])->name('devices.enrollments.copy');
    Route::resource('devices', BiometricDeviceController::class)->parameter('devices', 'device');

    Route::get('attendance/matrix', [AttendanceController::class, 'matrix'])->name('attendance.matrix');
    Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('attendance/exceptions', [AttendanceController::class, 'exceptions'])->name('attendance.exceptions');
    Route::get('attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
    Route::get('attendance/corrections', [AttendanceCorrectionController::class, 'index'])->name('attendance.corrections.index');
    Route::get('attendance/corrections/create', [AttendanceCorrectionController::class, 'create'])->name('attendance.corrections.create');
    Route::post('attendance/corrections', [AttendanceCorrectionController::class, 'store'])->name('attendance.corrections.store');
    Route::put('attendance/corrections/{correction}/approve', [AttendanceCorrectionController::class, 'approve'])->name('attendance.corrections.approve');
    Route::put('attendance/corrections/{correction}/reject', [AttendanceCorrectionController::class, 'reject'])->name('attendance.corrections.reject');
    Route::get('attendance/policies', [AttendancePolicyController::class, 'index'])->name('attendance.policies.index');
    Route::get('attendance/policies/{company}/edit', [AttendancePolicyController::class, 'edit'])->name('attendance.policies.edit');
    Route::put('attendance/policies/{company}', [AttendancePolicyController::class, 'update'])->name('attendance.policies.update');
    Route::resource('attendance/holidays', AttendanceHolidayController::class)->except(['show'])->names('attendance.holidays');
    Route::get('attendance/leaves', [EmployeeLeaveController::class, 'index'])->name('attendance.leaves.index');
    Route::get('attendance/leaves/create', [EmployeeLeaveController::class, 'create'])->name('attendance.leaves.create');
    Route::post('attendance/leaves', [EmployeeLeaveController::class, 'store'])->name('attendance.leaves.store');
    Route::put('attendance/leaves/{leave}/approve', [EmployeeLeaveController::class, 'approve'])->name('attendance.leaves.approve');
    Route::put('attendance/leaves/{leave}/reject', [EmployeeLeaveController::class, 'reject'])->name('attendance.leaves.reject');
    Route::get('attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import.form');
    Route::post('attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
    Route::resource('attendance', AttendanceController::class)->only(['index', 'create', 'store', 'destroy']);

    // Payroll periods: lock a company month and export payroll from attendance.
    Route::get('payroll/periods', [PayrollPeriodController::class, 'index'])->name('payroll.periods.index');
    Route::post('payroll/periods', [PayrollPeriodController::class, 'store'])->name('payroll.periods.store');
    Route::put('payroll/periods/{period}/lock', [PayrollPeriodController::class, 'lock'])->name('payroll.periods.lock');
    Route::put('payroll/periods/{period}/unlock', [PayrollPeriodController::class, 'unlock'])->name('payroll.periods.unlock');
    Route::get('payroll/periods/{period}/export', [PayrollPeriodController::class, 'export'])->name('payroll.periods.export');
});
