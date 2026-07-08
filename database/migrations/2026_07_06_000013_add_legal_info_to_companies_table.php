<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('legal_name_ar')->nullable()->after('name_en');
            $table->string('legal_name_en')->nullable()->after('legal_name_ar');
            $table->string('cr_number', 30)->nullable()->after('code');            // Commercial Registration
            $table->string('vat_number', 30)->nullable()->after('cr_number');       // VAT / Tax number
            $table->string('phone')->nullable()->after('vat_number');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('city')->nullable()->after('website');
            $table->string('address')->nullable()->after('city');
            $table->date('established_date')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_name_ar', 'legal_name_en', 'cr_number', 'vat_number',
                'phone', 'email', 'website', 'city', 'address', 'established_date',
            ]);
        });
    }
};
