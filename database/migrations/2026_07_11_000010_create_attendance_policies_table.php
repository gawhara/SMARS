<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('grace_minutes')->default(10);
            $table->unsignedSmallInteger('early_leave_grace_minutes')->default(0);
            $table->unsignedSmallInteger('full_day_minutes')->default(480);
            $table->unsignedSmallInteger('half_day_minutes')->default(240);
            $table->unsignedSmallInteger('overtime_after_minutes')->default(480);
            $table->unsignedTinyInteger('rounding_minutes')->default(1);
            $table->json('weekend_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
