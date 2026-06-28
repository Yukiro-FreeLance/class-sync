<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, code: string, sort_order: int}>
     */
    protected array $departments = [
        ['name' => 'Elementary', 'code' => 'elem', 'sort_order' => 1],
        ['name' => 'Junior High School', 'code' => 'jhs', 'sort_order' => 2],
        ['name' => 'Senior High School', 'code' => 'shs', 'sort_order' => 3],
    ];

    public function run(): void
    {
        foreach ($this->departments as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                array_merge($department, ['is_active' => true]),
            );
        }
    }
}
