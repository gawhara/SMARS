<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('replacement_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->dateTime('original_punch_at')->nullable();
            $table->string('original_punch_type')->nullable();
            $table->dateTime('requested_punch_at');
            $table->string('requested_punch_type');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['employee_id', 'requested_punch_at'], 'att_corr_employee_punch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};
