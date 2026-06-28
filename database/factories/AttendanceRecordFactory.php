<?php

namespace Database\Factories;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $timeIn = fake()->time('H:i:s');

        return [
            'student_id' => Student::factory(),
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'time_in' => $timeIn,
            'time_out' => fake()->optional(0.7)->time('H:i:s', $timeIn),
            'status' => fake()->randomElement(AttendanceStatus::cases()),
            'method' => fake()->randomElement(AttendanceMethod::cases()),
            'remarks' => fake()->optional(0.2)->sentence(),
            'latitude' => null,
            'longitude' => null,
            'device_id' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Present,
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Absent,
            'time_in' => null,
            'time_out' => null,
        ]);
    }
}
