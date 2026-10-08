<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permissions and functional roles from the removed modules. Listed by
     * name (not "everything except the current seed list") so permissions
     * or roles added by hand are never touched.
     */
    private const REMOVED_PERMISSIONS = [
        'leave.view', 'leave.apply', 'leave.approve', 'leave.manage',
        'attendance.view', 'attendance.manage',
        'payroll.view', 'payroll.manage',
        'recruitment.view', 'recruitment.manage',
        'departments.view', 'departments.create', 'departments.update', 'departments.delete',
        'activity.view', 'users.restore', 'roles.assign', 'profile.view',
    ];

    private const REMOVED_FUNCTIONAL_ROLES = [
        'HR Officer', 'Payroll Officer', 'Recruiter', 'Attendance Admin',
    ];

    /**
     * The leave, attendance, department, activity-log and soft-delete
     * features were removed along with the migrations that created their
     * schema. This cleans that schema and seed data out of any database
     * that already ran those migrations; on a fresh install every step is
     * a no-op.
     */
    public function up(): void
    {
        // Children before parents, so foreign keys never block a drop.
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_regularizations');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('department_shifts');
        Schema::dropIfExists('activity_logs');

        if (Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        Schema::dropIfExists('departments');

        // Without SoftDeletes, previously trashed users would silently
        // become active accounts again — finish deleting them instead.
        if (Schema::hasColumn('users', 'deleted_at')) {
            $trashedIds = DB::table('users')->whereNotNull('deleted_at')->pluck('id');

            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $trashedIds)
                ->delete();
            DB::table('users')->whereIn('id', $trashedIds)->delete();

            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // permission_role and role_user cascade on delete.
        DB::table('permissions')->whereIn('name', self::REMOVED_PERMISSIONS)->delete();
        DB::table('roles')
            ->where('type', 'functional')
            ->whereIn('name', self::REMOVED_FUNCTIONAL_ROLES)
            ->delete();
    }

    /**
     * Irreversible: the dropped schema no longer exists anywhere in code.
     */
    public function down(): void
    {
        //
    }
};
