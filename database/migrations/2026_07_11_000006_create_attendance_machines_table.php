<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_machines', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_model')->default('ZKTeco MB1000');
            $table->string('serial_number')->nullable()->unique();

            // Connection (CODEX §19): lan | vpn | ddns | static_ip
            $table->string('connection_type')->default('lan');
            $table->string('ip_address')->nullable();
            $table->string('domain')->nullable();
            $table->unsignedInteger('port')->default(4370); // ZKTeco default
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted at rest

            // Physical location only — devices are global org-wide (CODEX §19).
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_description')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');

            // Status: unknown | online | offline | unreachable | sync_failed
            $table->string('status')->default('unknown');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_successful_connection_at')->nullable();
            $table->timestamp('last_failed_connection_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('is_active');
            $table->index('connection_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_machines');
    }
};
