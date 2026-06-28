<?php

namespace App\Services\Students;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\TeacherScopeService;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentListService
{
    public function __construct(
        protected SettingsService $settings,
        protected TeacherScopeService $teacherScope,
    ) {}

    public function currentAcademicYearId(): ?int
    {
        return AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function schoolContext(): array
    {
        $general = $this->settings->getGroup('general');

        return [
            'school_name' => $general['school_name'] ?? config('app.name'),
            'school_address' => $general['school_address'] ?? null,
        ];
    }

    /**
     * @return Builder<Student>
     */
    public function masterListQuery(
        int $academicYearId,
        int $gradeLevelId,
        ?int $sectionId = null,
        bool $activeOnly = true,
        ?User $user = null,
    ): Builder {
        $query = $this->scopedStudentQuery($user)
            ->with(['gradeLevel.department', 'section'])
            ->where(function (Builder $q) use ($academicYearId, $gradeLevelId) {
                $q->where(function (Builder $inner) use ($academicYearId, $gradeLevelId) {
                    $inner->where('academic_year_id', $academicYearId)
                        ->where('grade_level_id', $gradeLevelId);
                })->orWhereHas('enrollments', function (Builder $enrollment) use ($academicYearId, $gradeLevelId) {
                    $enrollment->where('academic_year_id', $academicYearId)
                        ->where('grade_level_id', $gradeLevelId)
                        ->where('status', EnrollmentStatus::Enrolled);
                });
            })
            ->when($sectionId, fn (Builder $q) => $q->where(function (Builder $inner) use ($sectionId, $academicYearId) {
                $inner->where('section_id', $sectionId)
                    ->orWhereHas('enrollments', fn (Builder $enrollment) => $enrollment
                        ->where('academic_year_id', $academicYearId)
                        ->where('section_id', $sectionId)
                        ->where('status', EnrollmentStatus::Enrolled));
            }))
            ->when($activeOnly, fn (Builder $q) => $q->where('status', StudentStatus::Active))
            ->orderBy('section_id')
            ->orderBy('last_name')
            ->orderBy('first_name');

        return $query;
    }

    /**
     * @return Builder<Student>
     */
    public function classListQuery(
        int $academicYearId,
        int $sectionId,
        ?int $subjectId = null,
        bool $activeOnly = true,
        ?User $user = null,
    ): Builder {
        $query = $this->scopedStudentQuery($user)
            ->with(['gradeLevel.department', 'section'])
            ->where(function (Builder $q) use ($academicYearId, $sectionId) {
                $q->where(function (Builder $inner) use ($academicYearId, $sectionId) {
                    $inner->where('academic_year_id', $academicYearId)
                        ->where('section_id', $sectionId);
                })->orWhereHas('enrollments', function (Builder $enrollment) use ($academicYearId, $sectionId) {
                    $enrollment->where('academic_year_id', $academicYearId)
                        ->where('section_id', $sectionId)
                        ->where('status', EnrollmentStatus::Enrolled);
                });
            })
            ->when($subjectId, function (Builder $q) use ($subjectId, $academicYearId, $sectionId) {
                $scheduleIds = ClassSchedule::query()
                    ->where('academic_year_id', $academicYearId)
                    ->where('section_id', $sectionId)
                    ->where('subject_id', $subjectId)
                    ->pluck('id');

                if ($scheduleIds->isEmpty()) {
                    $q->whereRaw('1 = 0');

                    return;
                }

                $q->whereHas('enrollments', function (Builder $enrollment) use ($academicYearId, $scheduleIds) {
                    $enrollment->where('academic_year_id', $academicYearId)
                        ->whereHas('classSchedules', fn (Builder $schedule) => $schedule->whereIn('class_schedules.id', $scheduleIds));
                });
            })
            ->when($activeOnly, fn (Builder $q) => $q->where('status', StudentStatus::Active))
            ->orderBy('last_name')
            ->orderBy('first_name');

        return $query;
    }

    /**
     * @return Collection<string, Collection<int, Student>>
     */
    public function masterListGrouped(
        int $academicYearId,
        int $gradeLevelId,
        ?int $sectionId = null,
        bool $activeOnly = true,
        ?User $user = null,
    ): Collection {
        return $this->masterListQuery($academicYearId, $gradeLevelId, $sectionId, $activeOnly, $user)
            ->get()
            ->groupBy(fn (Student $student) => $student->section?->name ?? 'Unassigned');
    }

    public function sectionContext(int $sectionId, ?int $academicYearId = null): ?Section
    {
        return Section::query()
            ->with(['gradeLevel.department', 'adviser', 'academicYear', 'assignedRoom'])
            ->when($academicYearId, fn (Builder $q) => $q->where(function (Builder $inner) use ($academicYearId) {
                $inner->where('academic_year_id', $academicYearId)
                    ->orWhereNull('academic_year_id');
            }))
            ->find($sectionId);
    }

    /**
     * @return list<int>
     */
    public function accessibleSectionIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $this->teacherScope->accessibleSectionIds($user);
    }

    public function userBypassesScope(?User $user): bool
    {
        return $user && $this->teacherScope->bypassesScope($user);
    }

    /**
     * @return Builder<Student>
     */
    protected function scopedStudentQuery(?User $user): Builder
    {
        return Student::query();
    }

    public static function formatGender(?string $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            default => $gender ? strtoupper(substr($gender, 0, 1)) : '—',
        };
    }

    public static function formatName(Student $student, string $style = 'full'): string
    {
        if ($style === 'formal') {
            $middle = $student->middle_name ? ' '.strtoupper(substr($student->middle_name, 0, 1)).'.' : '';

            return trim("{$student->last_name}, {$student->first_name}{$middle}");
        }

        return $student->full_name;
    }
}
