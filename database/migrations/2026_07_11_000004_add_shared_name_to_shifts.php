<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('schedule_name_ar')->nullable()->after('shift_number');
        });

        $sharedName = DB::table('shifts')->whereNull('deleted_at')->orderBy('shift_number')->value('name_ar');

        if ($sharedName) {
            DB::table('shifts')->whereNull('deleted_at')->update(['schedule_name_ar' => $sharedName]);
        }
    }

    public function down(): void
    {
        Schema::table('shifts', fn (Blueprint $table) => $table->dropColumn('schedule_name_ar'));
    }
};
