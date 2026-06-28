<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    protected $model = GradeLevel::class;

    public function definition(): array
    {
        $sortOrder = fake()->unique()->numberBetween(0, 20);

        return [
            'name' => 'Grade '.$sortOrder,
            'code' => (string) $sortOrder,
            'sort_order' => $sortOrder,
        ];
    }
}
