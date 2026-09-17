<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\UserResource;
use App\Models\Department;
use Dedoc\Scramble\Attributes\Group;

#[Group('Departments', weight: 5)]
class DepartmentController extends Controller
{
    /**
     * List departments
     *
     * Small, bounded catalog (like Roles/Permissions) — no pagination.
     * Requires departments.view.
     */
    public function index()
    {
        $departments = Department::with('head')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return DepartmentResource::collection($departments);
    }

    /**
     * Get a department
     */
    public function show(Department $department)
    {
        $department->loadMissing('head')->loadCount('users');

        return new DepartmentResource($department);
    }

    /**
     * List a department's users
     *
     * Paginated, in the same shape as GET /users. Requires departments.view
     * — informational only, doesn't require users.view (mirrors the web
     * department-show page, which is gated the same way).
     */
    public function users(Department $department)
    {
        $users = $department->users()
            ->with(['role', 'department', 'functionalRoles'])
            ->orderBy('name')
            ->paginate(25);

        return UserResource::collection($users);
    }
}
