<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('penalty_date');
            $table->string('type');                       // warning | deduction | fine | suspension | other
            $table->string('reason');
            $table->decimal('amount', 12, 2)->default(0); // monetary deduction reflected on payroll
            $table->string('status')->default('active');  // active | cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'penalty_date']);
            $table->index(['status', 'penalty_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_penalties');
    }
};
