<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('shift_number')->default(1)->after('company_id');
            $table->index(['company_id', 'shift_number']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'shift_number']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('shift_number');
        });
    }
};
