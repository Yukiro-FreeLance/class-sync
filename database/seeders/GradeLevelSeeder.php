<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, code: string, department: string}>
     */
    protected array $levels = [
        ['name' => 'Kindergarten', 'code' => 'K', 'department' => 'elem'],
        ['name' => 'Grade 1', 'code' => '1', 'department' => 'elem'],
        ['name' => 'Grade 2', 'code' => '2', 'department' => 'elem'],
        ['name' => 'Grade 3', 'code' => '3', 'department' => 'elem'],
        ['name' => 'Grade 4', 'code' => '4', 'department' => 'elem'],
        ['name' => 'Grade 5', 'code' => '5', 'department' => 'elem'],
        ['name' => 'Grade 6', 'code' => '6', 'department' => 'elem'],
        ['name' => 'Grade 7', 'code' => '7', 'department' => 'jhs'],
        ['name' => 'Grade 8', 'code' => '8', 'department' => 'jhs'],
        ['name' => 'Grade 9', 'code' => '9', 'department' => 'jhs'],
        ['name' => 'Grade 10', 'code' => '10', 'department' => 'jhs'],
        ['name' => 'Grade 11', 'code' => '11', 'department' => 'shs'],
        ['name' => 'Grade 12', 'code' => '12', 'department' => 'shs'],
    ];

    public function run(): void
    {
        $departments = Department::query()->pluck('id', 'code');

        foreach ($this->levels as $index => $level) {
            GradeLevel::query()->updateOrCreate(
                ['code' => $level['code']],
                [
                    'name' => $level['name'],
                    'department_id' => $departments[$level['department']] ?? null,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
