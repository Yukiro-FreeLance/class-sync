<?php

namespace App\Services\Students;

use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SubjectSeeder;

class StudentReferenceDataService
{
    public function ensureExists(): void
    {
        if (Department::query()->doesntExist()) {
            app(DepartmentSeeder::class)->run();
        }

        if (GradeLevel::query()->doesntExist()) {
            app(GradeLevelSeeder::class)->run();
        }

        if (Section::query()->doesntExist()) {
            app(SectionSeeder::class)->run();
        }

        if (Subject::query()->doesntExist()) {
            app(SubjectSeeder::class)->run();
        }
    }
}
