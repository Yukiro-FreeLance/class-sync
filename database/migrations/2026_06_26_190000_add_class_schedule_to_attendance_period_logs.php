<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendance_period_logs', 'class_schedule_id')) {
            return;
        }

        Schema::table('attendance_period_logs', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        foreach ($this->foreignKeysOnColumn('attendance_period_logs', 'class_period_id') as $foreignKey) {
            DB::statement("ALTER TABLE `attendance_period_logs` DROP FOREIGN KEY `{$foreignKey}`");
        }

        Schema::table('attendance_period_logs', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'class_period_id', 'date']);
        });

        Schema::table('attendance_period_logs', function (Blueprint $table) {
            $table->foreignId('class_period_id')->nullable()->change();
            $table->foreignId('class_schedule_id')->nullable()->after('class_period_id')
                ->constrained('class_schedules')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_period_id')->references('id')->on('class_periods')->nullOnDelete();
            $table->unique(['student_id', 'class_schedule_id', 'date'], 'apl_student_schedule_date_uq');
            $table->index(['date', 'section_id', 'class_schedule_id'], 'apl_date_section_schedule_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendance_period_logs', 'class_schedule_id')) {
            return;
        }

        Schema::table('attendance_period_logs', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['class_schedule_id']);
            $table->dropForeign(['class_period_id']);
            $table->dropIndex('apl_date_section_schedule_idx');
            $table->dropUnique('apl_student_schedule_date_uq');
            $table->dropColumn('class_schedule_id');
        });

        Schema::table('attendance_period_logs', function (Blueprint $table) {
            $table->foreignId('class_period_id')->nullable(false)->change();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_period_id')->references('id')->on('class_periods')->cascadeOnDelete();
            $table->unique(['student_id', 'class_period_id', 'date']);
        });
    }

    /**
     * @return list<string>
     */
    protected function foreignKeysOnColumn(string $table, string $column): array
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return [];
        }

        $rows = DB::select(
            'SELECT CONSTRAINT_NAME as name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column],
        );

        return array_map(fn ($row) => $row->name, $rows);
    }
};
