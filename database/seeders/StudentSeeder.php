<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->first();

        if (! $academicYear) {
            return;
        }

        $gradeLevels = GradeLevel::query()->ordered()->with('sections')->get();

        if ($gradeLevels->isEmpty()) {
            return;
        }

        $counter = 1;

        for ($i = 0; $i < 50; $i++) {
            $gradeLevel = $gradeLevels->random();
            $section = $gradeLevel->sections->random();

            $student = Student::factory()->create([
                'student_number' => sprintf('STU-%04d', $counter++),
                'grade_level_id' => $gradeLevel->id,
                'section_id' => $section?->id,
                'academic_year_id' => $academicYear->id,
                'status' => StudentStatus::Active,
            ]);

            Guardian::query()->create([
                'student_id' => $student->id,
                'name' => fake()->name(),
                'relationship' => fake()->randomElement(['Mother', 'Father', 'Guardian']),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->safeEmail(),
                'address' => fake()->address(),
                'is_primary' => true,
            ]);

            if (fake()->boolean(40)) {
                Guardian::query()->create([
                    'student_id' => $student->id,
                    'name' => fake()->name(),
                    'relationship' => fake()->randomElement(['Mother', 'Father', 'Guardian', 'Aunt', 'Uncle']),
                    'phone' => fake()->phoneNumber(),
                    'email' => fake()->optional()->safeEmail(),
                    'is_primary' => false,
                ]);
            }
        }
    }
}
