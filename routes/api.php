<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');

    // Token auth (Sanctum), not the session guard: this route file sits in
    // the stateless `api` middleware group (no session middleware active),
    // so a session-based guard can never authenticate a request here — see
    // the login() docblock on AuthController for the credential-check side
    // of that.
    Route::middleware(['auth:sanctum', 'throttle:api', 'password.current'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');

        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');

        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('permission:users.view')
            ->name('users.show');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.update');

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');

        Route::get('/roles/{role}', [RoleController::class, 'show'])
            ->middleware('permission:roles.view')
            ->name('roles.show');

        Route::get('/permissions', [PermissionController::class, 'index'])
            ->middleware('permission:permissions.view')
            ->name('permissions.index');

        Route::get('/permissions/{permission}', [PermissionController::class, 'show'])
            ->middleware('permission:permissions.view')
            ->name('permissions.show');
    });
});
