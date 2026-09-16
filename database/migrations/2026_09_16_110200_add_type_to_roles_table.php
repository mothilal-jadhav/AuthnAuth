<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // 'hierarchy' roles (admin/manager/user) participate in ordinal
            // management comparisons via `level` (see UserPolicy). 'functional'
            // roles are additive permission grants only (e.g. Payroll Officer)
            // and never affect who-manages-whom.
            $table->string('type')->default('hierarchy')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
