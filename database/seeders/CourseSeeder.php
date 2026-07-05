<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * @var list<array{name: string, code: string}>
     */
    protected array $strands = [
        ['name' => 'Science, Technology, Engineering and Mathematics', 'code' => 'STEM'],
        ['name' => 'Accountancy, Business and Management', 'code' => 'ABM'],
        ['name' => 'Humanities and Social Sciences', 'code' => 'HUMSS'],
        ['name' => 'General Academic Strand', 'code' => 'GAS'],
        ['name' => 'Technical-Vocational-Livelihood', 'code' => 'TVL'],
    ];

    public function run(): void
    {
        $shsId = Department::query()->where('code', 'shs')->value('id');

        if (! $shsId) {
            return;
        }

        GradeLevel::query()
            ->where('department_id', $shsId)
            ->ordered()
            ->each(function (GradeLevel $gradeLevel) {
                foreach ($this->strands as $strand) {
                    Course::query()->updateOrCreate(
                        [
                            'grade_level_id' => $gradeLevel->id,
                            'code' => $strand['code'],
                        ],
                        [
                            'name' => $strand['name'],
                        ],
                    );
                }
            });
    }
}
