<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_daily_summaries', function (Blueprint $table): void {
            $table->string('reconciliation_status')->default('open')->after('exception_codes');
            $table->foreignId('reconciled_by')->nullable()->after('reconciliation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable()->after('reconciled_by');
            $table->text('reconciliation_notes')->nullable()->after('reconciled_at');
            $table->index(['reconciliation_status', 'attendance_date'], 'attendance_reconciliation_queue_idx');
        });

        DB::table('attendance_daily_summaries')
            ->where('has_exception', false)
            ->update(['reconciliation_status' => 'not_required']);
    }

    public function down(): void
    {
        Schema::table('attendance_daily_summaries', function (Blueprint $table): void {
            $table->dropIndex('attendance_reconciliation_queue_idx');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn(['reconciliation_status', 'reconciled_at', 'reconciliation_notes']);
        });
    }
};
