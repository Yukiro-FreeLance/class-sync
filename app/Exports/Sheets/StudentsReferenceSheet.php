<?php

namespace App\Exports\Sheets;

use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsReferenceSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Reference';
    }

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $rows = [
            ['Departments', 'Grade Levels', 'Sections (by grade)', 'Academic Years', 'Status Values'],
        ];

        $departments = Department::query()->ordered()->get();
        $grades = GradeLevel::query()->with('department')->orderBy('sort_order')->get();
        $sections = Section::query()->with('gradeLevel')->orderBy('grade_level_id')->orderBy('name')->get();
        $years = AcademicYear::query()->orderByDesc('start_date')->get();
        $statuses = collect(StudentStatus::options())->keys()->values();

        $max = max($departments->count(), $grades->count(), $sections->count(), $years->count(), $statuses->count());

        for ($i = 0; $i < $max; $i++) {
            $department = $departments[$i] ?? null;
            $grade = $grades[$i] ?? null;
            $section = $sections[$i] ?? null;
            $year = $years[$i] ?? null;

            $rows[] = [
                $department?->name ?? '',
                $grade?->name ?? '',
                $section ? ($section->gradeLevel?->name.' — '.$section->name) : '',
                $year?->name ?? '',
                $statuses[$i] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Import notes'];
        $rows[] = ['• Leave student_number blank to auto-generate.'];
        $rows[] = ['• Required: first_name, last_name, grade_level, academic_year.'];
        $rows[] = ['• Dates use YYYY-MM-DD format.'];
        $rows[] = ['• gender: male or female'];
        $rows[] = ['• Use exact names from this sheet for grade_level, section, and academic_year.'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '475569'],
                ],
            ],
        ];
    }
}
