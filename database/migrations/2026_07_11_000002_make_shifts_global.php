<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'shift_number']);
            $table->dropColumn('company_id');
            $table->index('shift_number');
        });

        DB::table('shifts')->orderBy('id')->get(['id'])->each(function ($shift, $index): void {
            DB::table('shifts')->where('id', $shift->id)->update(['shift_number' => $index + 1]);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['shift_number']);
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['company_id', 'shift_number']);
        });
    }
};
