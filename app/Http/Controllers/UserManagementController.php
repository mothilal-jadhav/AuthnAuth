<?php

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Actions\UpdateUser;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    /**
     * The list itself is fetched client-side from GET /api/v1/users (filters,
     * pagination and per-row can.update/can.delete come from there), using
     * the session cookie via Sanctum's stateful API. Only what the API
     * doesn't expose is passed as props.
     */
    public function index(Request $request)
    {
        $roleCounts = User::join('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.name, count(*) as total')
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        return Inertia::render('Users/Index', [
            'roleCounts' => $roleCounts,
            'can' => ['create' => $request->user()->hasPermission('users.create')],
        ]);
    }

    /**
     * After a successful store() this same page shows the new account's
     * one-time credentials, read from the flash so a reload clears them.
     */
    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->roleOptions($this->assignableRoles(forCreate: true)),
            'createdUser' => session('created_user'),
        ]);
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

    public function edit(Request $request, User $user)
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'functional_role_ids' => $user->functionalRoles()->pluck('roles.id'),
            ],
            'roles' => $this->roleOptions($this->assignableRoles()),
            'functionalRoles' => $this->roleOptions(Role::functional()->orderBy('name')->get()),
            'canAssignFunctionalRoles' => $request->user()->can('assignFunctionalRoles', $user),
        ]);
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

        $updateUser->execute($request->user(), $user, $validated, $functionalRoleIds);

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

    /**
     * Roles the current actor is allowed to assign, so the create/edit
     * dropdowns never even offer a role the backend would reject.
     *
     * $forCreate mirrors UserPolicy::create()'s same-level allowance (a
     * manager may create another manager) without extending it to editing
     * an existing user's role, which stays strictly-lower.
     *
     * @return Collection<int, Role>
     */
    private function assignableRoles(bool $forCreate = false): Collection
    {
        $actorRole = auth()->user()->role;

        if ($actorRole->isAdmin()) {
            return Role::hierarchy()->orderBy('name')->get();
        }

        $operator = $forCreate ? '<=' : '<';

        return Role::hierarchy()->where('level', $operator, $actorRole->level)->orderBy('name')->get();
    }

    /**
     * Only what a <select>/checkbox needs — never whole Role models as props.
     *
     * @param  Collection<int, Role>  $roles
     * @return list<array{id: int, name: string}>
     */
    private function roleOptions(Collection $roles): array
    {
        return $roles->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name])->values()->all();
    }
}
