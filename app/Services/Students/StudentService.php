<?php

namespace App\Services\Students;

use App\Enums\AuditAction;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Repositories\StudentRepository;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentService
{
    public function __construct(
        protected StudentRepository $repository,
        protected AuditLogService $auditLog,
        protected StudentEnrollmentService $enrollmentService,
    ) {}

    public function find(int $id): ?Student
    {
        return $this->repository->find($id);
    }

    public function findByStudentNumber(string $studentNumber): ?Student
    {
        return $this->repository->findByStudentNumber($studentNumber);
    }

    /**
     * @return Collection<int, Student>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    /**
     * @return LengthAwarePaginator<Student>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * @return Collection<int, Student>
     */
    public function search(string $term, int $limit = 50): Collection
    {
        return $this->repository->search($term, $limit);
    }

    public function create(array $data): Student
    {
        if (empty($data['student_number'])) {
            $data['student_number'] = $this->generateStudentNumber();
        }

        $student = $this->repository->create($data);
        $this->generateQrCode($student);
        $this->auditLog->logCreated($student);

        if (! empty($data['grade_level_id']) && ! empty($data['academic_year_id'])) {
            $this->enrollmentService->enroll($student, [
                'academic_year_id' => $data['academic_year_id'],
                'grade_level_id' => $data['grade_level_id'],
                'section_id' => $data['section_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'enrollment_date' => $data['enrollment_date'] ?? now()->toDateString(),
                'class_schedule_ids' => ! empty($data['section_id'])
                    ? $this->enrollmentService->defaultClassScheduleIds(
                        (int) $data['section_id'],
                        (int) $data['academic_year_id'],
                    )
                    : [],
            ]);
        }

        return $student->fresh();
    }

    public function update(Student $student, array $data): Student
    {
        $oldValues = $student->getAttributes();
        $student = $this->repository->update($student, $data);
        $this->auditLog->logUpdated($student, $oldValues);

        return $student;
    }

    public function archive(Student $student): bool
    {
        $oldValues = $student->getAttributes();
        $student->update(['status' => StudentStatus::Inactive]);
        $result = $this->repository->delete($student);

        $this->auditLog->log(
            AuditAction::Update,
            $student,
            'Archived student',
            ['old' => $oldValues, 'archived' => true],
        );

        return $result;
    }

    public function restore(Student $student): Student
    {
        $this->repository->restore($student);
        $student->update(['status' => StudentStatus::Active]);

        $this->auditLog->log(
            AuditAction::Restore,
            $student,
            'Restored archived student',
            ['status' => StudentStatus::Active->value],
        );

        return $student->fresh();
    }

    public function forceDelete(Student $student): bool
    {
        if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
            Storage::disk('public')->delete($student->qr_code);
        }

        $qrPath = "qr-codes/{$student->student_number}.svg";
        if (Storage::disk('public')->exists($qrPath)) {
            Storage::disk('public')->delete($qrPath);
        }

        $this->auditLog->logDeleted($student);

        return $this->repository->forceDelete($student);
    }

    /** @deprecated Use archive() or forceDelete() */
    public function delete(Student $student): bool
    {
        return $this->archive($student);
    }

    public function generateQrCode(Student $student): string
    {
        $path = "qr-codes/{$student->student_number}.svg";

        $qrContent = json_encode([
            'student_number' => $student->student_number,
            'name' => $student->full_name,
        ]);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->generate($qrContent);

        Storage::disk('public')->put($path, $svg);
        $student->update(['qr_code' => $student->student_number]);

        return $path;
    }

    public function getQrCodeUrl(Student $student): ?string
    {
        if (! $student->qr_code) {
            return null;
        }

        $path = "qr-codes/{$student->student_number}.svg";

        if (! Storage::disk('public')->exists($path)) {
            $this->generateQrCode($student);
        }

        return Storage::disk('public')->url($path);
    }

    protected function generateStudentNumber(): string
    {
        do {
            $number = 'STU-'.strtoupper(Str::random(8));
        } while ($this->repository->findByStudentNumber($number));

        return $number;
    }
}
