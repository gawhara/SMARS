<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Organization links (CODEX §9-10)
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();

            // Identity / contact
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('phone_2')->nullable()->unique();
            $table->string('status')->default('active');

            // Employee identifiers (global uniqueness — CODEX §10-11)
            $table->string('employee_code')->unique();
            $table->string('financial_employee_id')->nullable()->unique();
            $table->string('hr_employee_id')->unique();
            $table->string('national_id', 10)->unique();

            // Names as printed on official documents
            $table->string('iqama_full_name_arabic')->nullable();
            $table->string('iqama_full_name_english')->nullable();
            $table->string('full_name_arabic')->nullable();
            $table->string('full_name_english')->nullable();

            // Nationality / personal
            $table->string('nationality')->nullable();          // ISO2 country code
            $table->string('saudi_non_saudi')->nullable();      // saudi | non_saudi
            $table->string('gender')->nullable();               // male | female
            $table->date('birth_date')->nullable();
            $table->date('iqama_expiry')->nullable();

            // Passport (required + globally unique — CODEX §16)
            $table->string('passport_id')->unique();
            $table->string('passport_full_name_arabic')->nullable();
            $table->string('passport_full_name_english')->nullable();
            $table->date('passport_expiry')->nullable();

            // Job / contract
            $table->string('job_title')->nullable();
            $table->string('contract_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Banking
            $table->string('bank')->nullable();                 // banks.code
            $table->string('iban')->nullable();
            $table->string('branch')->nullable();               // free-text bank branch

            // Salary components (CODEX §11 — decimal(12,2))
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('transportation_allowance', 12, 2)->default(0);
            $table->decimal('training_labor_wages', 12, 2)->default(0);
            $table->decimal('previous_dues', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // GOSI-related
            $table->decimal('basic_salary_gosi', 12, 2)->default(0);
            $table->decimal('housing_allowance_gosi', 12, 2)->default(0);
            $table->decimal('other_gosi_items', 12, 2)->default(0);
            $table->decimal('diff_registered_housing_allowance', 12, 2)->default(0);

            // Deductions
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('delay_deduction', 12, 2)->default(0);
            $table->decimal('leave_deduction', 12, 2)->default(0);
            $table->decimal('warnings_penalties', 12, 2)->default(0);
            $table->decimal('insurance_deduction', 12, 2)->default(0);
            $table->decimal('loans', 12, 2)->default(0);
            $table->decimal('social_insurance_saudi', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Payout distribution
            $table->decimal('cash', 12, 2)->default(0);
            $table->decimal('al_rajhi_transfer', 12, 2)->default(0);
            $table->decimal('bank_albilad_transfer', 12, 2)->default(0);
            $table->decimal('riyad_bank_transfer', 12, 2)->default(0);
            $table->decimal('remaining_salary', 12, 2)->default(0);

            $table->string('employment_status')->default('active');

            // Audit authors (CODEX §11, §24)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Filter / search indexes (CODEX §8, §11)
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('saudi_non_saudi');
            $table->index('status');
            $table->index('employment_status');
            $table->index('iqama_expiry');
            $table->index('passport_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
