<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DepartmentController;
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

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile');

Route::put('/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');

Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('profile.password.update');

Route::get('/password/change', [ChangePasswordController::class, 'showForm'])
    ->middleware('auth')
    ->name('password.change');

Route::post('/password/change', [ChangePasswordController::class, 'update'])
    ->middleware('auth')
    ->name('password.change.update');
