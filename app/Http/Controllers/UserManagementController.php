<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role')
            ->when($request->filled('role'), fn ($query) => $query->whereHas(
                'role',
                fn ($q) => $q->where('name', $request->input('role'))
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $roleCounts = User::join('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.name, count(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        return view('users.index', compact('users', 'roleCounts'));
    }

    public function create()
    {
        $roles = $this->assignableRoles();

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $temporaryPassword = Str::password(12);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $temporaryPassword,
        ]);
        $user->role_id = $validated['role_id'];
        $user->must_change_password = true;
        $user->save();

        return redirect()
            ->route('users.create')
            ->with('created_user', [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $temporaryPassword,
                'role' => $user->role->name,
            ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $roles = $this->assignableRoles();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $this->authorize('assignRole', [User::class, (int) $validated['role_id']]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        $user->role_id = $validated['role_id'];
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Roles the current actor is allowed to assign, so the create/edit
     * dropdowns never even offer a role the backend would reject.
     */
    private function assignableRoles()
    {
        $actorLevel = auth()->user()->role->level ?? 0;

        if (auth()->user()->role->name === 'admin') {
            return Role::orderBy('name')->get();
        }

        return Role::where('level', '<', $actorLevel)->orderBy('name')->get();
    }
}
