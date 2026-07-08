<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that receive audit-author columns (CODEX §24).
     */
    private array $tables = ['companies', 'branches', 'departments', 'positions', 'shifts'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by');
                $table->dropConstrainedForeignId('updated_by');
            });
        }
    }
};
