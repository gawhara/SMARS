<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_daily_summaries', function (Blueprint $table) {
            $table->unsignedInteger('overtime_minutes')->default(0)->after('early_leave_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_daily_summaries', fn (Blueprint $table) => $table->dropColumn('overtime_minutes'));
    }
};
