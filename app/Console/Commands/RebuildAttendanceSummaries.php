<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceDailySummaryService;
use Illuminate\Console\Command;

class RebuildAttendanceSummaries extends Command
{
    protected $signature = 'attendance:rebuild-summaries';
    protected $description = 'Recalculate daily attendance summaries from matched punch records';

    public function handle(AttendanceDailySummaryService $service): int
    {
        $count = 0;

        AttendanceRecord::matched()->orderBy('id')->chunkById(500, function ($records) use ($service, &$count): void {
            $service->rebuildForRecords($records);
            $count += $records->count();
        });

        $this->info("Rebuilt summaries from {$count} matched punches.");

        return self::SUCCESS;
    }
}
