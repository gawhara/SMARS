<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latest = DB::table('shifts')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(2)
            ->get()
            ->reverse()
            ->values();

        if ($latest->isEmpty()) {
            return;
        }

        $slots = DB::table('shifts')->whereNull('deleted_at')->orderBy('id')->limit(2)->get();

        foreach ($latest as $index => $source) {
            $slot = $slots[$index] ?? null;

            if ($slot) {
                DB::table('shifts')->where('id', $slot->id)->update([
                    'shift_number' => $index + 1,
                    'name_ar' => 'الوردية '.($index + 1),
                    'name_en' => 'Shift '.($index + 1),
                    'start_time' => $source->start_time,
                    'end_time' => $source->end_time,
                    'is_active' => $source->is_active,
                    'updated_at' => now(),
                ]);
            }
        }

        $keepIds = $slots->take($latest->count())->pluck('id');
        DB::table('shifts')->whereNull('deleted_at')->whereNotIn('id', $keepIds)->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        // Consolidation intentionally keeps the two canonical global shift slots.
    }
};
