<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\DeviceEnrollmentController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LanguageController;
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
    Route::get('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'index'])->name('devices.enrollments.index');
    Route::post('devices/{device}/enrollments', [DeviceEnrollmentController::class, 'store'])->name('devices.enrollments.store');
    Route::delete('devices/{device}/enrollments/{enrollment}', [DeviceEnrollmentController::class, 'destroy'])->name('devices.enrollments.destroy');
    Route::post('devices/{device}/enrollments/copy', [DeviceEnrollmentController::class, 'copy'])->name('devices.enrollments.copy');
    Route::resource('devices', BiometricDeviceController::class)->parameter('devices', 'device');

    Route::get('attendance/matrix', [AttendanceController::class, 'matrix'])->name('attendance.matrix');
    Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import.form');
    Route::post('attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
    Route::resource('attendance', AttendanceController::class)->only(['index', 'create', 'store', 'destroy']);
});
