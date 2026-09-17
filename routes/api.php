<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepartmentController;
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
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');

        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');

        // Must be registered before GET /users/{user} — otherwise "trashed"
        // would be matched as a {user} route-model-binding value.
        Route::get('/users/trashed', [UserController::class, 'trashed'])
            ->middleware('permission:users.restore')
            ->name('users.trashed');

        // {id}, not {user}: restore() looks the user up via onlyTrashed()
        // itself, since implicit route-model binding excludes soft-deleted rows.
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])
            ->middleware('permission:users.restore')
            ->name('users.restore');

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

        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware('permission:departments.view')
            ->name('departments.index');

        Route::get('/departments/{department}', [DepartmentController::class, 'show'])
            ->middleware('permission:departments.view')
            ->name('departments.show');

        Route::get('/departments/{department}/users', [DepartmentController::class, 'users'])
            ->middleware('permission:departments.view')
            ->name('departments.users');
    });
});
