<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  array{search?: string, grade?: string, section?: string, status?: string}  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        return Student::query()
            ->with(['gradeLevel', 'section', 'academicYear'])
            ->when($this->filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('rfid_tag', 'like', "%{$search}%");
                });
            })
            ->when($this->filters['grade'] ?? null, fn ($q, $grade) => $q->where('grade_level_id', $grade))
            ->when($this->filters['section'] ?? null, fn ($q, $section) => $q->where('section_id', $section))
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
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
        ];
    }

    /**
     * @param  Student  $student
     * @return list<mixed>
     */
    public function map($student): array
    {
        return [
            $student->student_number,
            $student->first_name,
            $student->last_name,
            $student->middle_name,
            $student->gender,
            $student->birth_date?->format('Y-m-d'),
            $student->address,
            $student->gradeLevel?->name,
            $student->section?->name,
            $student->academicYear?->name,
            $student->status?->value,
            $student->rfid_tag,
            $student->medical_notes,
            $student->enrollment_date?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
