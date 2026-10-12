<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * `can` only decides which quick-action cards to show; each target route
     * enforces its own permission.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'role' => ['name' => $user->role->name, 'type' => $user->role->type->value],
            'can' => [
                'viewUsers' => $user->hasPermission('users.view'),
                'createUsers' => $user->hasPermission('users.create'),
            ],
        ]);
    }
}
