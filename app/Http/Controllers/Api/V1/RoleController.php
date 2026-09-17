<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Dedoc\Scramble\Attributes\Group;

#[Group('Roles', weight: 3)]
class RoleController extends Controller
{
    /**
     * List roles
     *
     * Both hierarchy roles (admin/manager/user — drive management
     * authorization via their ordinal `level`) and functional roles
     * (additive-only permission grants, e.g. Payroll Officer — `level` is
     * meaningless for these) are returned; `type` distinguishes them. Small,
     * bounded catalog, no pagination. Requires roles.view.
     */
    public function index()
    {
        return RoleResource::collection(Role::orderBy('type')->orderBy('name')->get());
    }

    /**
     * Get a role
     */
    public function show(Role $role)
    {
        return new RoleResource($role);
    }
}
