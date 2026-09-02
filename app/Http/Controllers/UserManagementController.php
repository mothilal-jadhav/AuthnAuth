<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->orderBy('name')
            ->get();

        $admins = $users->filter(
            fn ($user) => $user->role?->name === 'admin'
        );

        $managers = $users->filter(
            fn ($user) => $user->role?->name === 'manager'
        );

        $normalUsers = $users->filter(
            fn ($user) => $user->role?->name === 'user'
        );

        return view('users.index', compact(
            'admins',
            'managers',
            'normalUsers'
        ));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'email' => [
                'required', 
                'email', 
                'max:255', 
                'unique:users,email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $temporaryPassword = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $temporaryPassword,
            'role_id' => $validated['role_id'],
        ]);

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

    private function canManageUser(User $target): bool
    {
        $currentUser = auth()->user();

        // Admin can manage everyone.
        if ($currentUser->role->name === 'admin') {
            return true;
        }

        // Manager can manage only normal users.
        if (
            $currentUser->role->name === 'manager' &&
            $target->role?->name === 'user'
        ) {
            return true;
        }

        // Normal users cannot manage anyone.
        return false;
    }

    public function edit(User $user)
    {
        abort_unless($this->canManageUser($user), 403);

        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($this->canManageUser($user), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
            'email' => [
                'required', 
                'email', 
                'max:255', 
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => [
                'required',
                'exists:roles,id',
            ],
        ]);

        // Manager cannot promote a normal user to manager/admin.
        if (
            auth()->user()->role->name === 'manager' &&
            Role::find($validated['role_id'])->name !== 'user'
        ) {
            abort(403);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_unless($this->canManageUser($user), 403);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}