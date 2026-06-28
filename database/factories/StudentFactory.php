<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'student_number' => 'STU-'.fake()->unique()->numerify('####'),
            'rfid_tag' => fake()->optional(0.3)->uuid(),
            'qr_code' => Str::uuid()->toString(),
            'photo' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => fake()->optional(0.4)->firstName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'address' => fake()->address(),
            'grade_level_id' => GradeLevel::factory(),
            'section_id' => null,
            'course_id' => null,
            'academic_year_id' => AcademicYear::factory(),
            'status' => StudentStatus::Active,
            'medical_notes' => fake()->optional(0.2)->sentence(),
            'enrollment_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Student $student) {
            if ($student->section_id !== null) {
                return;
            }

            $section = Section::query()
                ->where('grade_level_id', $student->grade_level_id)
                ->inRandomOrder()
                ->first();

            if ($section) {
                $student->update(['section_id' => $section->id]);
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentStatus::Inactive,
        ]);
    }
}
