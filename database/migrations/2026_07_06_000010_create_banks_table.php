<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();       // short slug, e.g. RAJHI
            $table->string('iban_code', 2);          // 2-digit code embedded in the IBAN (positions 5-6)
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('iban_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
