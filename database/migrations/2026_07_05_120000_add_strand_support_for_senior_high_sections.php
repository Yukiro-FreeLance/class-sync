<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->hasIndex('courses', 'courses_code_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        }

        if (! $this->hasIndex('courses', 'courses_grade_level_id_code_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique(['grade_level_id', 'code']);
            });
        }

        if (! Schema::hasColumn('sections', 'course_id')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->foreignId('course_id')->nullable()->after('grade_level_id')->constrained()->nullOnDelete();
            });
        }

        if (! $this->hasIndex('sections', 'sections_grade_level_id_lookup_index')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->index('grade_level_id', 'sections_grade_level_id_lookup_index');
            });
        }

        if ($this->hasIndex('sections', 'sections_grade_level_id_name_unique')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->dropUnique(['grade_level_id', 'name']);
            });
        }

        if (! $this->hasIndex('sections', 'sections_grade_course_name_unique')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->unique(['grade_level_id', 'course_id', 'name'], 'sections_grade_course_name_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('sections', 'sections_grade_course_name_unique')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->dropUnique('sections_grade_course_name_unique');
            });
        }

        if (! $this->hasIndex('sections', 'sections_grade_level_id_name_unique')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->unique(['grade_level_id', 'name']);
            });
        }

        if ($this->hasIndex('sections', 'sections_grade_level_id_lookup_index')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->dropIndex('sections_grade_level_id_lookup_index');
            });
        }

        if (Schema::hasColumn('sections', 'course_id')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_id');
            });
        }

        if ($this->hasIndex('courses', 'courses_grade_level_id_code_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropUnique(['grade_level_id', 'code']);
            });
        }

        if (! $this->hasIndex('courses', 'courses_code_unique')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unique(['code']);
            });
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $indexes = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$indexName]);

        return $indexes !== [];
    }
};
