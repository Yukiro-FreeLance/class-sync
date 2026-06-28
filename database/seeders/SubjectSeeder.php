<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::query()->pluck('id', 'code');

        $subjects = [
            ['code' => 'MATH', 'name' => 'Mathematics', 'department' => null],
            ['code' => 'ENG', 'name' => 'English', 'department' => null],
            ['code' => 'SCI', 'name' => 'Science', 'department' => null],
            ['code' => 'FIL', 'name' => 'Filipino', 'department' => null],
            ['code' => 'AP', 'name' => 'Araling Panlipunan', 'department' => null],
            ['code' => 'MAPEH', 'name' => 'MAPEH', 'department' => null],
            ['code' => 'TLE', 'name' => 'Technology and Livelihood Education', 'department' => 'jhs'],
            ['code' => 'ESP', 'name' => 'Edukasyon sa Pagpapakatao', 'department' => 'jhs'],
            ['code' => 'STEM', 'name' => 'STEM Strand Core', 'department' => 'shs'],
            ['code' => 'ABM', 'name' => 'ABM Strand Core', 'department' => 'shs'],
        ];

        foreach ($subjects as $subject) {
            Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                [
                    'name' => $subject['name'],
                    'department_id' => isset($subject['department']) ? $departments[$subject['department']] : null,
                    'is_active' => true,
                ],
            );
        }
    }
}
