<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Dedoc\Scramble\Attributes\Group;

#[Group('Permissions', weight: 4)]
class PermissionController extends Controller
{
    /**
     * List permissions
     *
     * The full permission catalog, grouped via `group` for display. Includes
     * groups with no routes behind them yet (leave/payroll/attendance/
     * recruitment — deliberate scaffolding for future HRMS modules, see
     * PermissionSeeder). Small, bounded catalog, no pagination. Requires
     * permissions.view.
     */
    public function index()
    {
        return PermissionResource::collection(Permission::orderBy('group')->orderBy('name')->get());
    }

    /**
     * Get a permission
     */
    public function show(Permission $permission)
    {
        return new PermissionResource($permission);
    }
}
