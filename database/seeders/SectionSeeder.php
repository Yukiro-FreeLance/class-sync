<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $sectionNames = ['A', 'B', 'C'];

    public function run(): void
    {
        GradeLevel::query()
            ->ordered()
            ->each(function (GradeLevel $gradeLevel) {
                foreach ($this->sectionNames as $index => $name) {
                    Section::query()->updateOrCreate(
                        [
                            'grade_level_id' => $gradeLevel->id,
                            'name' => $name,
                        ],
                        [
                            'room' => sprintf('%s-%d%02d', $name, $gradeLevel->sort_order + 1, $index + 1),
                        ],
                    );
                }
            });
    }
}
