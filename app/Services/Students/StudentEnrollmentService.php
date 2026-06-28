<?php

namespace App\Services\Students;

use App\DTOs\Students\BulkEnrollmentResult;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function enroll(Student $student, array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($student, $data) {
            $classScheduleIds = $this->normalizeClassScheduleIds(
                $data['class_schedule_ids'] ?? [],
                (int) ($data['section_id'] ?? 0),
                (int) $data['academic_year_id'],
            );

            $enrollment = StudentEnrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $data['academic_year_id'],
                ],
                [
                    'grade_level_id' => $data['grade_level_id'],
                    'section_id' => $data['section_id'] ?? null,
                    'course_id' => $data['course_id'] ?? null,
                    'status' => $data['status'] ?? EnrollmentStatus::Enrolled->value,
                    'enrollment_date' => $data['enrollment_date'] ?? now()->toDateString(),
                    'remarks' => $data['remarks'] ?? null,
                ],
            );

            $enrollment->classSchedules()->sync($classScheduleIds);

            $this->syncStudentRecord($student, $enrollment);

            return $enrollment->fresh([
                'academicYear',
                'gradeLevel',
                'section',
                'course',
                'classSchedules.subject',
                'classSchedules.teacher',
                'classSchedules.room',
            ]);
        });
    }

    /**
     * @param  list<int>  $studentIds
     * @param  array<string, mixed>  $data
     */
    public function bulkEnroll(array $studentIds, array $data): BulkEnrollmentResult
    {
        $result = new BulkEnrollmentResult;
        $section = Section::query()->findOrFail($data['section_id']);

        if ((int) $section->grade_level_id !== (int) $data['grade_level_id']) {
            throw ValidationException::withMessages([
                'section_id' => 'The selected section does not belong to the chosen grade level.',
            ]);
        }

        $classScheduleIds = $this->normalizeClassScheduleIds(
            $data['class_schedule_ids'] ?? [],
            (int) $data['section_id'],
            (int) $data['academic_year_id'],
        );

        foreach (array_unique($studentIds) as $studentId) {
            $result = $this->processStudent(
                $result,
                (int) $studentId,
                function (Student $student) use ($data, $classScheduleIds) {
                    $this->enroll($student, array_merge($data, [
                        'class_schedule_ids' => $classScheduleIds,
                    ]));
                },
            );
        }

        return $result;
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<int>  $classScheduleIds
     */
    public function bulkAssignSubjects(
        array $studentIds,
        int $academicYearId,
        int $sectionId,
        array $classScheduleIds,
        bool $merge = false,
    ): BulkEnrollmentResult {
        $result = new BulkEnrollmentResult;

        $validScheduleIds = $this->normalizeClassScheduleIds(
            $classScheduleIds,
            $sectionId,
            $academicYearId,
        );

        foreach (array_unique($studentIds) as $studentId) {
            $result = $this->processStudent(
                $result,
                (int) $studentId,
                function (Student $student) use ($academicYearId, $sectionId, $validScheduleIds, $merge) {
                    $enrollment = StudentEnrollment::query()
                        ->where('student_id', $student->id)
                        ->where('academic_year_id', $academicYearId)
                        ->first();

                    if (! $enrollment) {
                        throw new \RuntimeException('No enrollment record found for this academic year.');
                    }

                    if ((int) $enrollment->section_id !== $sectionId) {
                        throw new \RuntimeException('Student is not enrolled in the selected section.');
                    }

                    $scheduleIds = $merge
                        ? $enrollment->classSchedules()->pluck('class_schedules.id')
                            ->merge($validScheduleIds)
                            ->unique()
                            ->values()
                            ->all()
                        : $validScheduleIds;

                    $enrollment->classSchedules()->sync($scheduleIds);
                    $this->syncStudentRecord($student, $enrollment->fresh());
                },
            );
        }

        return $result;
    }

    /**
     * @return Collection<int, ClassSchedule>
     */
    public function availableClasses(
        int $sectionId,
        int $academicYearId,
        ?string $semester = null,
    ): Collection {
        return ClassSchedule::query()
            ->with(['subject', 'teacher', 'room'])
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $academicYearId)
            ->when($semester, fn ($query) => $query->where('semester', $semester))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, object{subject_id: int, subject: ?Subject, schedules: Collection<int, ClassSchedule>}>
     */
    public function availableSubjectsGrouped(
        int $sectionId,
        int $academicYearId,
        ?string $semester = null,
    ): Collection {
        return $this->availableClasses($sectionId, $academicYearId, $semester)
            ->groupBy('subject_id')
            ->map(fn (Collection $schedules, int $subjectId) => (object) [
                'subject_id' => $subjectId,
                'subject' => $schedules->first()?->subject,
                'schedules' => $schedules,
            ])
            ->values();
    }

    public function defaultClassScheduleIds(int $sectionId, int $academicYearId): array
    {
        return $this->availableClasses($sectionId, $academicYearId)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int>  $subjectIds
     * @return list<int>
     */
    public function scheduleIdsForSubjects(
        int $sectionId,
        int $academicYearId,
        array $subjectIds,
        ?string $semester = null,
    ): array {
        if ($subjectIds === []) {
            return [];
        }

        return $this->availableClasses($sectionId, $academicYearId, $semester)
            ->whereIn('subject_id', $subjectIds)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<int|string>  $scheduleIds
     * @return list<int>
     */
    public function normalizeClassScheduleIds(
        array $scheduleIds,
        int $sectionId,
        int $academicYearId,
    ): array {
        $ids = collect($scheduleIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $validIds = ClassSchedule::query()
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('id', $ids)
            ->pluck('id');

        if ($validIds->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'class_schedule_ids' => 'One or more selected classes do not belong to this section and academic year.',
            ]);
        }

        return $validIds->all();
    }

    protected function processStudent(
        BulkEnrollmentResult $result,
        int $studentId,
        callable $callback,
    ): BulkEnrollmentResult {
        $result = new BulkEnrollmentResult(
            processed: $result->processed + 1,
            failed: $result->failed,
            errors: $result->errors,
        );

        try {
            $student = Student::query()->findOrFail($studentId);
            $callback($student);
        } catch (\Throwable $exception) {
            $name = Student::query()->find($studentId)?->full_name ?? "Student #{$studentId}";

            return new BulkEnrollmentResult(
                processed: $result->processed,
                failed: $result->failed + 1,
                errors: array_merge($result->errors, ["{$name}: {$exception->getMessage()}"]),
            );
        }

        return $result;
    }

    protected function syncStudentRecord(Student $student, StudentEnrollment $enrollment): void
    {
        $currentYearId = AcademicYear::query()->where('is_current', true)->value('id');

        if ((int) $enrollment->academic_year_id !== (int) $currentYearId) {
            return;
        }

        $student->update([
            'academic_year_id' => $enrollment->academic_year_id,
            'grade_level_id' => $enrollment->grade_level_id,
            'section_id' => $enrollment->section_id,
            'course_id' => $enrollment->course_id,
            'enrollment_date' => $enrollment->enrollment_date,
        ]);
    }
}
