<?php

namespace App\Services\Students;

use App\DTOs\Students\StudentImportResult;
use App\Enums\AuditAction;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentImportService
{
    public function __construct(
        protected StudentService $studentService,
        protected AuditLogService $auditLog,
    ) {}

    public function import(UploadedFile $file, bool $updateExisting = false): StudentImportResult
    {
        app(StudentReferenceDataService::class)->ensureExists();

        $rows = Excel::toArray([], $file)[0] ?? [];

        if ($rows === []) {
            return new StudentImportResult(0, 0, [
                ['row' => 0, 'message' => 'The file is empty.'],
            ]);
        }

        $headings = array_map(fn ($value) => Str::slug((string) $value, '_'), array_shift($rows));
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedRows = [];

        $gradeLevels = GradeLevel::query()->get();
        $sections = Section::query()->with('gradeLevel')->get();
        $academicYears = AcademicYear::query()->get()->keyBy(fn ($y) => Str::lower($y->name));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRow($headings, $row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'gender' => ['nullable', 'string', 'max:20'],
                'birth_date' => ['nullable', 'date'],
                'address' => ['nullable', 'string'],
                'grade_level' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'academic_year' => ['required', 'string'],
                'status' => ['nullable', 'string'],
                'student_number' => ['nullable', 'string', 'max:50'],
                'rfid_tag' => ['nullable', 'string', 'max:100'],
                'medical_notes' => ['nullable', 'string'],
                'enrollment_date' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => implode(' ', $validator->errors()->all()),
                ];

                continue;
            }

            $grade = $this->resolveGradeLevel($gradeLevels, $data['grade_level']);

            if (! $grade) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Unknown grade level \"{$data['grade_level']}\".",
                ];

                continue;
            }

            $year = $academicYears->get(Str::lower(trim($data['academic_year'])));

            if (! $year) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Unknown academic year \"{$data['academic_year']}\".",
                ];

                continue;
            }

            $sectionId = null;

            if (! blank($data['section'])) {
                $sectionName = Str::lower(trim($data['section']));
                $section = $sections->first(function ($item) use ($grade, $sectionName) {
                    return $item->grade_level_id === $grade->id
                        && Str::lower($item->name) === $sectionName;
                });

                if (! $section) {
                    $skipped++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => "Section \"{$data['section']}\" not found for {$grade->name}.",
                    ];

                    continue;
                }

                $sectionId = $section->id;
            }

            $status = StudentStatus::Active->value;

            if (! blank($data['status'])) {
                $statusValue = Str::lower(trim($data['status']));

                if (! array_key_exists($statusValue, StudentStatus::options())) {
                    $skipped++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'message' => "Invalid status \"{$data['status']}\".",
                    ];

                    continue;
                }

                $status = $statusValue;
            }

            $payload = [
                'student_number' => blank($data['student_number']) ? null : trim($data['student_number']),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'middle_name' => blank($data['middle_name']) ? null : trim($data['middle_name']),
                'gender' => blank($data['gender']) ? null : Str::lower(trim($data['gender'])),
                'birth_date' => $data['birth_date'] ?? null,
                'address' => blank($data['address']) ? null : trim($data['address']),
                'grade_level_id' => $grade->id,
                'section_id' => $sectionId,
                'academic_year_id' => $year->id,
                'status' => $status,
                'rfid_tag' => blank($data['rfid_tag']) ? null : trim($data['rfid_tag']),
                'medical_notes' => blank($data['medical_notes']) ? null : trim($data['medical_notes']),
                'enrollment_date' => $data['enrollment_date'] ?? null,
            ];

            $existing = $this->findExistingStudent($payload);

            if ($existing && ! $updateExisting) {
                $skipped++;
                $skippedRows[] = [
                    'row' => $rowNumber,
                    'message' => $this->duplicateSkipMessage($existing, $payload),
                ];

                continue;
            }

            try {
                DB::transaction(function () use ($payload, $existing, $updateExisting, &$imported) {
                    if ($existing && $updateExisting) {
                        unset($payload['student_number']);
                        $this->studentService->update($existing, $payload);
                    } else {
                        $this->studentService->create($payload);
                    }

                    $imported++;
                });
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($imported > 0) {
            $this->auditLog->log(
                AuditAction::Import,
                null,
                "Imported {$imported} student record(s)",
                ['imported' => $imported, 'skipped' => $skipped, 'errors' => count($errors), 'duplicates' => count($skippedRows)],
            );
        }

        return new StudentImportResult($imported, $skipped, $errors, $skippedRows);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapRow(array $headings, array $row): array
    {
        $data = [];

        foreach ($headings as $index => $heading) {
            if ($heading === '') {
                continue;
            }

            $value = $row[$index] ?? null;
            $data[$heading] = $this->normalizeValue($heading, $value);
        }

        return $data;
    }

    protected function resolveGradeLevel(Collection $gradeLevels, string $input): ?GradeLevel
    {
        $normalized = Str::lower(trim($input));

        return $gradeLevels->first(function (GradeLevel $grade) use ($normalized) {
            return Str::lower($grade->name) === $normalized
                || Str::lower($grade->code) === $normalized;
        });
    }

    protected function normalizeValue(string $heading, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($heading, ['birth_date', 'enrollment_date'], true)) {
            return $this->parseDate($value);
        }

        if (in_array($heading, $this->stringFields(), true)) {
            $value = $this->castSpreadsheetString($value);

            if (in_array($heading, ['student_number', 'rfid_tag'], true) && ($value === '' || $value === '0')) {
                return null;
            }

            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * @return list<string>
     */
    protected function stringFields(): array
    {
        return [
            'student_number',
            'first_name',
            'last_name',
            'middle_name',
            'gender',
            'address',
            'grade_level',
            'section',
            'academic_year',
            'status',
            'rfid_tag',
            'medical_notes',
        ];
    }

    protected function castSpreadsheetString(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return is_float($value) && floor($value) == $value
                ? (string) (int) $value
                : rtrim(rtrim((string) $value, '0'), '.');
        }

        return trim((string) $value);
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function findExistingStudent(array $payload): ?Student
    {
        if ($payload['student_number']) {
            $existing = Student::query()
                ->where('student_number', $payload['student_number'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if ($payload['rfid_tag']) {
            return Student::query()
                ->where('rfid_tag', $payload['rfid_tag'])
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function duplicateSkipMessage(Student $existing, array $payload): string
    {
        if ($payload['student_number'] && $existing->student_number === $payload['student_number']) {
            return "Already registered — student number {$existing->student_number}.";
        }

        if ($payload['rfid_tag'] && $existing->rfid_tag === $payload['rfid_tag']) {
            return "Already registered — RFID tag {$existing->rfid_tag} ({$existing->student_number}).";
        }

        return "Already registered — {$existing->student_number}.";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function isEmptyRow(array $data): bool
    {
        $meaningful = collect($data)->only([
            'student_number',
            'first_name',
            'last_name',
            'middle_name',
            'grade_level',
            'academic_year',
        ])->filter(fn ($value) => ! blank($value));

        return $meaningful->isEmpty();
    }
}
