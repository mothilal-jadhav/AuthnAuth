<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRegularizationApprovalController;
use App\Http\Controllers\AttendanceRegularizationController;
use App\Http\Controllers\AttendanceTeamController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentShiftController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->middleware('auth');

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login.submit');

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'role:admin']);

Route::get('/activity', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'permission:activity.view'])
    ->name('activity.index');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->middleware('guest')
    ->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware(['guest', 'throttle:password-reset'])
    ->name('password.email');

Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware(['guest', 'throttle:password-reset'])
    ->name('password.update');

Route::get('/users', [UserManagementController::class, 'index'])
    ->middleware(['auth', 'permission:users.view'])
    ->name('users.index');

Route::get('/users/create', [UserManagementController::class, 'create'])
    ->middleware(['auth', 'permission:users.create'])
    ->name('users.create');

Route::post('/users', [UserManagementController::class, 'store'])
    ->middleware(['auth', 'permission:users.create'])
    ->name('users.store');

Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
    ->middleware(['auth', 'permission:users.update'])
    ->name('users.edit');

Route::put('/users/{user}', [UserManagementController::class, 'update'])
    ->middleware(['auth', 'permission:users.update'])
    ->name('users.update');

Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
    ->middleware(['auth', 'permission:users.delete'])
    ->name('users.destroy');

Route::get('/users/trashed', [UserManagementController::class, 'trashed'])
    ->middleware(['auth', 'permission:users.restore'])
    ->name('users.trashed');

Route::post('/users/{id}/restore', [UserManagementController::class, 'restore'])
    ->middleware(['auth', 'permission:users.restore'])
    ->name('users.restore');

Route::get('/departments', [DepartmentController::class, 'index'])
    ->middleware(['auth', 'permission:departments.view'])
    ->name('departments.index');

Route::get('/departments/create', [DepartmentController::class, 'create'])
    ->middleware(['auth', 'permission:departments.create'])
    ->name('departments.create');

Route::get('/departments/{department}', [DepartmentController::class, 'show'])
    ->middleware(['auth', 'permission:departments.view'])
    ->name('departments.show');

Route::post('/departments', [DepartmentController::class, 'store'])
    ->middleware(['auth', 'permission:departments.create'])
    ->name('departments.store');

Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
    ->middleware(['auth', 'permission:departments.update'])
    ->name('departments.edit');

Route::put('/departments/{department}', [DepartmentController::class, 'update'])
    ->middleware(['auth', 'permission:departments.update'])
    ->name('departments.update');

Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
    ->middleware(['auth', 'permission:departments.delete'])
    ->name('departments.destroy');

Route::get('/leave', [LeaveRequestController::class, 'index'])
    ->middleware(['auth', 'permission:leave.apply'])
    ->name('leave.index');

Route::get('/leave/create', [LeaveRequestController::class, 'create'])
    ->middleware(['auth', 'permission:leave.apply'])
    ->name('leave.create');

Route::post('/leave', [LeaveRequestController::class, 'store'])
    ->middleware(['auth', 'permission:leave.apply'])
    ->name('leave.store');

Route::get('/leave/approvals', [LeaveApprovalController::class, 'index'])
    ->middleware(['auth', 'permission:leave.approve'])
    ->name('leave.approvals.index');

Route::put('/leave/approvals/{leaveRequest}/approve', [LeaveApprovalController::class, 'approve'])
    ->middleware(['auth', 'permission:leave.approve'])
    ->name('leave.approvals.approve');

Route::put('/leave/approvals/{leaveRequest}/reject', [LeaveApprovalController::class, 'reject'])
    ->middleware(['auth', 'permission:leave.approve'])
    ->name('leave.approvals.reject');

Route::get('/leave/types', [LeaveTypeController::class, 'index'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.index');

Route::get('/leave/types/create', [LeaveTypeController::class, 'create'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.create');

Route::post('/leave/types', [LeaveTypeController::class, 'store'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.store');

Route::get('/leave/types/{leaveType}/edit', [LeaveTypeController::class, 'edit'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.edit');

Route::put('/leave/types/{leaveType}', [LeaveTypeController::class, 'update'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.update');

Route::delete('/leave/types/{leaveType}', [LeaveTypeController::class, 'destroy'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.types.destroy');

Route::get('/leave/balances', [LeaveBalanceController::class, 'index'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.balances.index');

Route::get('/leave/balances/{leaveBalance}/edit', [LeaveBalanceController::class, 'edit'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.balances.edit');

Route::put('/leave/balances/{leaveBalance}', [LeaveBalanceController::class, 'update'])
    ->middleware(['auth', 'permission:leave.manage'])
    ->name('leave.balances.update');

Route::get('/leave/{leaveRequest}', [LeaveRequestController::class, 'show'])
    ->middleware('auth')
    ->name('leave.show');

Route::delete('/leave/{leaveRequest}', [LeaveRequestController::class, 'cancel'])
    ->middleware('auth')
    ->name('leave.cancel');

Route::get('/attendance', [AttendanceController::class, 'index'])
    ->middleware('auth')
    ->name('attendance.index');

Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
    ->middleware('auth')
    ->name('attendance.clock-in');

Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
    ->middleware('auth')
    ->name('attendance.clock-out');

Route::get('/attendance/regularize', [AttendanceRegularizationController::class, 'create'])
    ->middleware('auth')
    ->name('attendance.regularize.create');

Route::post('/attendance/regularize', [AttendanceRegularizationController::class, 'store'])
    ->middleware('auth')
    ->name('attendance.regularize.store');

Route::get('/attendance/regularizations', [AttendanceRegularizationApprovalController::class, 'index'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.regularizations.index');

Route::put('/attendance/regularizations/{attendanceRegularization}/approve', [AttendanceRegularizationApprovalController::class, 'approve'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.regularizations.approve');

Route::put('/attendance/regularizations/{attendanceRegularization}/reject', [AttendanceRegularizationApprovalController::class, 'reject'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.regularizations.reject');

Route::get('/attendance/team', [AttendanceTeamController::class, 'index'])
    ->middleware(['auth', 'permission:attendance.view'])
    ->name('attendance.team.index');

Route::get('/attendance/shifts', [DepartmentShiftController::class, 'index'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.shifts.index');

Route::get('/attendance/shifts/{department}/edit', [DepartmentShiftController::class, 'edit'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.shifts.edit');

Route::put('/attendance/shifts/{department}', [DepartmentShiftController::class, 'update'])
    ->middleware(['auth', 'permission:attendance.manage'])
    ->name('attendance.shifts.update');

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile');

Route::put('/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');

Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('profile.password.update');

Route::post('/profile/verify-password', [ProfileController::class, 'verifyPassword'])
    ->middleware(['auth', 'throttle:password-verify'])
    ->name('profile.password.verify');

Route::get('/password/change', [ChangePasswordController::class, 'showForm'])
    ->middleware('auth')
    ->name('password.change');

Route::post('/password/change', [ChangePasswordController::class, 'update'])
    ->middleware('auth')
    ->name('password.change.update');
