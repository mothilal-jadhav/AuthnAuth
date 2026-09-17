<?php

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Actions\RestoreUser;
use App\Actions\UpdateUser;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['role', 'department'])
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
        $roles = $this->assignableRoles(forCreate: true);
        $departments = Department::orderBy('name')->get();

        return view('users.create', compact('roles', 'departments'));
    }

    public function store(StoreUserRequest $request, CreateUser $createUser)
    {
        ['user' => $user, 'temporary_password' => $temporaryPassword] = $createUser->execute($request->validated());

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
        $departments = Department::orderBy('name')->get();
        $functionalRoles = Role::functional()->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'departments', 'functionalRoles'));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $validated = $request->validated();

        $this->authorize('assignRole', [User::class, (int) $validated['role_id']]);

        // Checkboxes submit nothing at all when every box is unchecked, so a
        // hidden marker field (not the checkbox array itself) is what tells
        // us this section of the form was actually submitted.
        $functionalRoleIds = null;

        if ($request->boolean('functional_role_ids_submitted')) {
            $this->authorize('assignFunctionalRoles', $user);

            $functionalRoleIds = $validated['functional_role_ids'] ?? [];
        }

        $updateUser->execute($user, $validated, $functionalRoleIds);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, DeleteUser $deleteUser)
    {
        $this->authorize('delete', $user);

        $deleteUser->execute($user);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function trashed()
    {
        $users = User::onlyTrashed()
            ->with('role')
            ->orderBy('deleted_at', 'desc')
            ->paginate(25);

        return view('users.trashed', compact('users'));
    }

    public function restore(int $id, RestoreUser $restoreUser)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $user);

        $restoreUser->execute($user);

        return redirect()
            ->route('users.trashed')
            ->with('success', 'User restored successfully.');
    }

    /**
     * Roles the current actor is allowed to assign, so the create/edit
     * dropdowns never even offer a role the backend would reject.
     *
     * $forCreate mirrors UserPolicy::create()'s same-level allowance (a
     * manager may create another manager) without extending it to editing
     * an existing user's role, which stays strictly-lower.
     */
    private function assignableRoles(bool $forCreate = false)
    {
        $actorRole = auth()->user()->role;

        if ($actorRole->name === 'admin') {
            return Role::hierarchy()->orderBy('name')->get();
        }

        $operator = $forCreate ? '<=' : '<';

        return Role::hierarchy()->where('level', $operator, $actorRole->level)->orderBy('name')->get();
    }
}
