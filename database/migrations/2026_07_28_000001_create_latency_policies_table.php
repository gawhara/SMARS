<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('latency_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Late-entry rule (policy sections 3–6), made per-policy configurable.
            $table->unsignedSmallInteger('grace_minutes')->default(9);
            $table->boolean('round_up_to_hour')->default(true);
            $table->decimal('multiplier', 5, 2)->default(2.00);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('latency_policies');
    }
};
