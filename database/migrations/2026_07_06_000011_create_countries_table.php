<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedSmallInteger('priority')->default(0); // higher = shown first
            $table->timestamps();

            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
