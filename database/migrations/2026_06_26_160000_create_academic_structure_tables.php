<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sort_order');
        });

        Schema::table('grade_levels', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('building')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('room')->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('grade_level_id')->constrained()->nullOnDelete();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('semester', 20);
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->index(['section_id', 'academic_year_id', 'semester']);
            $table->index(['teacher_id', 'day_of_week']);
        });

        $this->backfillDepartments();
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('subjects');

        Schema::table('sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::dropIfExists('rooms');

        Schema::table('grade_levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('departments');
    }

    protected function backfillDepartments(): void
    {
        if (! Schema::hasTable('grade_levels')) {
            return;
        }

        $now = now();
        $departments = [
            ['name' => 'Elementary', 'code' => 'elem', 'sort_order' => 1],
            ['name' => 'Junior High School', 'code' => 'jhs', 'sort_order' => 2],
            ['name' => 'Senior High School', 'code' => 'shs', 'sort_order' => 3],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(
                ['code' => $department['code']],
                array_merge($department, ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]),
            );
        }

        $departmentIds = DB::table('departments')->pluck('id', 'code');

        DB::table('grade_levels')->orderBy('id')->get()->each(function ($grade) use ($departmentIds) {
            $code = (string) $grade->code;
            $departmentCode = match (true) {
                in_array($code, ['K', 'k', '0'], true) => 'elem',
                is_numeric($code) && (int) $code <= 6 => 'elem',
                is_numeric($code) && (int) $code <= 10 => 'jhs',
                default => 'shs',
            };

            DB::table('grade_levels')->where('id', $grade->id)->update([
                'department_id' => $departmentIds[$departmentCode] ?? null,
            ]);
        });
    }
};
