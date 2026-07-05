<?php

namespace Database\Seeders;

use App\Models\Course;
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
            ->with('department')
            ->ordered()
            ->each(function (GradeLevel $gradeLevel) {
                if ($gradeLevel->isSeniorHigh()) {
                    $this->seedSeniorHighSections($gradeLevel);

                    return;
                }

                foreach ($this->sectionNames as $index => $name) {
                    Section::query()->updateOrCreate(
                        [
                            'grade_level_id' => $gradeLevel->id,
                            'course_id' => null,
                            'name' => $name,
                        ],
                        [
                            'room' => sprintf('%s-%d%02d', $name, $gradeLevel->sort_order + 1, $index + 1),
                        ],
                    );
                }
            });
    }

    protected function seedSeniorHighSections(GradeLevel $gradeLevel): void
    {
        $courses = Course::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->orderBy('code')
            ->get();

        if ($courses->isEmpty()) {
            return;
        }

        foreach ($courses as $course) {
            foreach (['A', 'B'] as $index => $name) {
                Section::query()->updateOrCreate(
                    [
                        'grade_level_id' => $gradeLevel->id,
                        'course_id' => $course->id,
                        'name' => $name,
                    ],
                    [
                        'room' => sprintf('%s-%s-%d', $course->code, $name, $index + 1),
                    ],
                );
            }
        }
    }
}
