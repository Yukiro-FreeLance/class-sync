<?php

namespace App\Exports;

use App\Models\Student;
use App\Services\Students\StudentListService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassListExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /** @param Collection<int, Student> $students */
    public function __construct(protected Collection $students) {}

    public function collection(): Collection
    {
        return $this->students;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['#', 'Student No.', 'Last Name', 'First Name', 'Middle Name', 'Sex', 'Birthdate', 'Status'];
    }

    /**
     * @param  Student  $student
     * @return list<mixed>
     */
    public function map($student): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $student->student_number,
            $student->last_name,
            $student->first_name,
            $student->middle_name,
            StudentListService::formatGender($student->gender),
            $student->birth_date?->format('Y-m-d'),
            $student->status?->label(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
