<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

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
        $user->department_id = $validated['department_id'] ?? null;
        $user->must_change_password = true;
        $user->save();

        ActivityLog::record(
            'user.created',
            $user,
            auth()->user()->name.' created this account.',
            ['role' => $user->role->name]
        );

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

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $this->authorize('assignRole', [User::class, (int) $validated['role_id']]);

        $oldEmail = $user->email;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        $user->role_id = $validated['role_id'];
        $user->department_id = $validated['department_id'] ?? null;
        $user->save();

        $changes = $user->getChanges();
        unset($changes['updated_at']);

        ActivityLog::record(
            'user.updated',
            $user,
            auth()->user()->name.' updated this account.',
            $changes
        );

        if (array_key_exists('email', $changes)) {
            Notification::route('mail', array_unique([$oldEmail, $user->email]))
                ->notify(new EmailChangedNotification($oldEmail, $user->email, auth()->user()->name));
        }

        // Checkboxes submit nothing at all when every box is unchecked, so a
        // hidden marker field (not the checkbox array itself) is what tells
        // us this section of the form was actually submitted.
        $functionalRolesSubmitted = $request->boolean('functional_role_ids_submitted');

        if ($functionalRolesSubmitted) {
            $this->authorize('assignFunctionalRoles', $user);

            $functionalRoleIds = $validated['functional_role_ids'] ?? [];

            $user->functionalRoles()->sync($functionalRoleIds);

            ActivityLog::record(
                'user.functional_roles_updated',
                $user,
                auth()->user()->name.' updated this account\'s functional roles.',
                ['functional_role_ids' => $functionalRoleIds]
            );
        }

        if (array_key_exists('role_id', $changes) || $functionalRolesSubmitted) {
            Cache::forget("user:{$user->id}:permissions");
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        ActivityLog::record(
            'user.deleted',
            $user,
            auth()->user()->name.' deleted this account.'
        );

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

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $user);

        $user->restore();

        ActivityLog::record(
            'user.restored',
            $user,
            auth()->user()->name.' restored this account.'
        );

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
