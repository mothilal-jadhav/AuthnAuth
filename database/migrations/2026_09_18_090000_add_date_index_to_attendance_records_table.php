<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // The existing (user_id, date) unique index can't serve a
            // date-only lookup (leftmost-prefix rule), so the team roster's
            // `WHERE date = ?` query full-scans the table. A standalone
            // index on `date` fixes that.
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });
    }
};
