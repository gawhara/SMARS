<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            // Global employee match (null = unmatched, kept for review — CODEX §19).
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_user_id')->nullable();

            $table->dateTime('punch_at');
            $table->string('punch_type')->default('unknown'); // in | out | unknown
            $table->string('raw_punch_type')->nullable();
            $table->string('verification_type')->nullable();  // fingerprint | face | card | password
            $table->string('source')->default('manual');      // device | import | manual

            // Snapshot of the employee's org placement at punch time.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('sync_batch_id')->nullable()->constrained('attendance_sync_batches')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('company_id');
            $table->index('punch_at');
            $table->index(['device_user_id', 'punch_at']);
            // Guards against re-importing the same punch.
            $table->unique(['attendance_machine_id', 'device_user_id', 'punch_at'], 'attendance_unique_punch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
