<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('device_user_id'); // enrolment id used on the device (employee code)
            $table->foreignId('source_machine_id')->nullable()->constrained('attendance_machines')->nullOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // An employee is enrolled at most once per device.
            $table->unique(['attendance_machine_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_enrollments');
    }
};
