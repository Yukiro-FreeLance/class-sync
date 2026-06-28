<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('enrolled');
            $table->date('enrollment_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['academic_year_id', 'grade_level_id', 'section_id'], 'student_enroll_year_grade_section_idx');
        });

        Schema::create('student_enrollment_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_enrollment_id', 'class_schedule_id'], 'student_enrollment_class_unique');
        });

        $this->backfillFromStudents();
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_classes');
        Schema::dropIfExists('student_enrollments');
    }

    protected function backfillFromStudents(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $now = now();

        DB::table('students')->whereNull('deleted_at')->orderBy('id')->each(function ($student) use ($now) {
            $enrollmentId = DB::table('student_enrollments')->insertGetId([
                'student_id' => $student->id,
                'academic_year_id' => $student->academic_year_id,
                'grade_level_id' => $student->grade_level_id,
                'section_id' => $student->section_id,
                'course_id' => $student->course_id,
                'status' => 'enrolled',
                'enrollment_date' => $student->enrollment_date,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($student->section_id) {
                $classScheduleIds = DB::table('class_schedules')
                    ->where('section_id', $student->section_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->pluck('id');

                foreach ($classScheduleIds as $classScheduleId) {
                    DB::table('student_enrollment_classes')->insert([
                        'student_enrollment_id' => $enrollmentId,
                        'class_schedule_id' => $classScheduleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
};
