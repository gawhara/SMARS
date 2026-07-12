<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('first_in_at')->nullable();
            $table->dateTime('last_out_at')->nullable();
            $table->unsignedSmallInteger('punch_count')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('scheduled_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->string('status')->default('present');
            $table->boolean('has_exception')->default(false);
            $table->json('exception_codes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'attendance_daily_employee_date');
            $table->index(['attendance_date', 'has_exception']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily_summaries');
    }
};
