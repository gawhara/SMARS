<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->uuid('schedule_id')->nullable()->after('id')->index();
        });

        if (DB::table('shifts')->whereNull('deleted_at')->exists()) {
            DB::table('shifts')->whereNull('deleted_at')->update(['schedule_id' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['schedule_id']);
            $table->dropColumn('schedule_id');
        });
    }
};
