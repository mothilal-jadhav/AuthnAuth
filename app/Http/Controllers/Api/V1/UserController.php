<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Actions\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;

#[Group('Users', weight: 2)]
class UserController extends Controller
{
    /**
     * List users
     *
     * Paginated, filterable, sortable. Gated by the users.view permission
     * only — like the web /users index, results aren't filtered by the
     * viewer's position in the management hierarchy.
     */
    public function index(IndexUserRequest $request)
    {
        $filters = $request->validated();

        $users = User::with(['role', 'functionalRoles'])
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereHas(
                'role',
                fn ($q) => $q->where('name', $role)
            ))
            ->when($filters['name'] ?? null, fn ($query, $name) => $query->where(
                'name', 'like', '%'.addcslashes($name, '%_\\').'%'
            ))
            ->when($filters['email'] ?? null, fn ($query, $email) => $query->where(
                'email', 'like', '%'.addcslashes($email, '%_\\').'%'
            ))
            ->when(($filters['status'] ?? null) === 'pending', fn ($query) => $query->where('must_change_password', true))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('must_change_password', false))
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * Create a user
     *
     * Creates an account with a system-generated temporary password
     * (returned once, in `meta.temporary_password` — never re-exposed by any
     * other endpoint) and forces a password change on first login. Requires
     * users.create plus UserPolicy::create's hierarchy check for the given
     * role_id — e.g. a manager may create another manager or a user, never
     * an admin.
     */
    public function store(StoreUserRequest $request, CreateUser $createUser)
    {
        $result = $createUser->execute($request->validated());
        $user = $result['user']->load(['role', 'functionalRoles']);

        return (new UserResource($user))
            ->additional(['meta' => ['temporary_password' => $result['temporary_password']]])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a user
     */
    public function show(User $user)
    {
        return new UserResource($user->load(['role', 'functionalRoles']));
    }

    /**
     * Update a user
     *
     * Updates name/email/role. If `functional_role_ids` is
     * present in the body at all — including an empty array — the user's
     * additive functional roles are synced to exactly that set; omitting the
     * key entirely leaves them untouched. Requires users.update plus
     * UserPolicy's rules: no self-management, a strictly higher hierarchy
     * level than the target, and no assigning a role/functional-role the
     * actor isn't authorized to grant.
     */
    #[Response(403, description: 'Blocked by UserPolicy — self-management, insufficient hierarchy level, or an unauthorized role/functional-role assignment.')]
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $validated = $request->validated();

        $this->authorize('assignRole', [User::class, (int) $validated['role_id']]);

        // Unlike the web form (which needs a hidden marker field to tell
        // "every checkbox unchecked" apart from "not submitted" over HTML),
        // a JSON client's intent is unambiguous from key presence alone.
        $functionalRoleIds = null;

        if ($request->has('functional_role_ids')) {
            $this->authorize('assignFunctionalRoles', $user);

            $functionalRoleIds = $validated['functional_role_ids'];
        }

        $user = $updateUser->execute($request->user(), $user, $validated, $functionalRoleIds);

        return new UserResource($user->fresh(['role', 'functionalRoles']));
    }

    /**
     * Delete a user
     *
     * Permanently deletes the account and revokes its API tokens. Requires
     * users.delete plus UserPolicy::delete.
     */
    #[Response(403, description: 'Blocked by UserPolicy — self-deletion, insufficient hierarchy level, or this is the last remaining admin.')]
    public function destroy(User $user, DeleteUser $deleteUser)
    {
        $this->authorize('delete', $user);

        $deleteUser->execute($user);

        return response()->noContent();
    }
}
