<?php

use App\Http\Middleware\EnsurePasswordIsCurrent;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'password.current' => EnsurePasswordIsCurrent::class,
        ]);

        $middleware->appendToGroup('web', ForcePasswordChange::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->appendToGroup('web', HandleInertiaRequests::class);

        // Lets the React pages call /api/v1/* with the existing session
        // cookie (Sanctum SPA auth) instead of holding a bearer token in JS.
        // Only requests whose Referer/Origin matches sanctum.stateful get
        // session + CSRF middleware; bearer-token clients without such a
        // header are untouched.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $shouldRenderJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($shouldRenderJson);

        // Laravel's default message includes the internal model class name
        // (e.g. "No query results for model [App\Models\User] 5") — fine
        // for the web 404 page (which never prints it), but an unnecessary
        // implementation-detail leak for JSON clients. Handler::render()
        // converts ModelNotFoundException to NotFoundHttpException (keeping
        // the original as ->getPrevious()) before any custom render()
        // callback runs, so that's the type this has to target.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($shouldRenderJson) {
            if ($shouldRenderJson($request) && $e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });
    })->create();
