<?php

namespace App\Exports\Sheets;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Students';
    }

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $grade = GradeLevel::query()->where('name', 'Grade 10')->first()
            ?? GradeLevel::query()->orderBy('sort_order')->get()->get(10)
            ?? GradeLevel::query()->orderByDesc('sort_order')->first();

        $gradeName = $grade?->name ?? 'Grade 10';
        $yearName = AcademicYear::query()->where('is_current', true)->value('name')
            ?? AcademicYear::query()->orderByDesc('id')->value('name')
            ?? now()->format('Y').'-'.(now()->year + 1);

        return [
            [
                'student_number',
                'first_name',
                'last_name',
                'middle_name',
                'gender',
                'birth_date',
                'address',
                'grade_level',
                'section',
                'academic_year',
                'status',
                'rfid_tag',
                'medical_notes',
                'enrollment_date',
            ],
            [
                '',
                'Maria',
                'Santos',
                'L.',
                'female',
                '2010-05-15',
                '123 Main St',
                $gradeName,
                'A',
                $yearName,
                'active',
                '',
                '',
                now()->toDateString(),
            ],
            [
                '',
                'Juan',
                'Dela Cruz',
                '',
                'male',
                '2010-08-22',
                '456 Oak Ave',
                $gradeName,
                'B',
                $yearName,
                'active',
                'RFID-001',
                '',
                now()->toDateString(),
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '7C3AED'],
                ],
            ],
        ];
    }
}
