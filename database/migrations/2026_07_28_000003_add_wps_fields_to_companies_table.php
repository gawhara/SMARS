<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // Wage Protection System / Mudad establishment details for the SIF header.
            $table->string('wps_establishment_id')->nullable()->after('code');
            $table->string('employer_bank_code', 4)->nullable()->after('wps_establishment_id');
            $table->string('employer_iban', 34)->nullable()->after('employer_bank_code');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn(['wps_establishment_id', 'employer_bank_code', 'employer_iban']);
        });
    }
};
