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
        ?string $gender = null,
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
            ->orderBy('section_id');

        return self::orderByGenderThenName(self::applyGenderFilter($query, $gender));
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
        ?string $gender = null,
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
            ->when($activeOnly, fn (Builder $q) => $q->where('status', StudentStatus::Active));

        return self::orderByGenderThenName(self::applyGenderFilter($query, $gender));
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
        ?string $gender = null,
    ): Collection {
        return $this->masterListQuery($academicYearId, $gradeLevelId, $sectionId, $activeOnly, $user, $gender)
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

    /**
     * @return array<string, string>
     */
    public static function genderFilterOptions(): array
    {
        return [
            '' => 'All genders',
            'male' => 'Male',
            'female' => 'Female',
        ];
    }

    /**
     * @param  Builder<Student>  $query
     * @return Builder<Student>
     */
    public static function applyGenderFilter(Builder $query, ?string $gender): Builder
    {
        if (blank($gender)) {
            return $query;
        }

        return match (self::genderGroupKey($gender)) {
            'male' => $query->whereRaw('LOWER(gender) IN (?, ?)', ['male', 'm']),
            'female' => $query->whereRaw('LOWER(gender) IN (?, ?)', ['female', 'f']),
            default => $query,
        };
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, Student>
     */
    public static function filterCollectionByGender(Collection $students, ?string $gender): Collection
    {
        if (blank($gender)) {
            return $students;
        }

        $key = self::genderGroupKey($gender);

        return $students
            ->filter(fn (Student $student) => self::genderGroupKey($student->gender) === $key)
            ->values();
    }

    public static function genderGroupKey(?string $gender): string
    {
        return match (strtolower(trim((string) $gender))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'unspecified',
        };
    }

    public static function genderGroupLabel(string $key): string
    {
        return match ($key) {
            'male' => 'Male',
            'female' => 'Female',
            default => 'Unspecified',
        };
    }

    public static function genderSortOrder(?string $gender): int
    {
        return match (self::genderGroupKey($gender)) {
            'male' => 0,
            'female' => 1,
            default => 2,
        };
    }

    public static function genderOrderExpression(string $column = 'gender'): string
    {
        $column = preg_replace('/[^a-z_]/', '', strtolower($column)) ?: 'gender';

        return "CASE WHEN LOWER({$column}) IN ('male', 'm') THEN 0 WHEN LOWER({$column}) IN ('female', 'f') THEN 1 ELSE 2 END";
    }

    /**
     * @param  Builder<Student>  $query
     * @return Builder<Student>
     */
    public static function orderByGenderThenName(Builder $query): Builder
    {
        return $query
            ->orderByRaw(self::genderOrderExpression().' ASC')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name');
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, Student>
     */
    public static function sortByGenderThenName(Collection $students): Collection
    {
        return $students
            ->sortBy([
                fn (Student $student) => self::genderSortOrder($student->gender),
                fn (Student $student) => mb_strtolower($student->last_name),
                fn (Student $student) => mb_strtolower($student->first_name),
                fn (Student $student) => mb_strtolower((string) $student->middle_name),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<string, Collection<int, Student>>
     */
    public static function groupByGender(Collection $students): Collection
    {
        $order = ['male' => 0, 'female' => 1, 'unspecified' => 2];

        return self::sortByGenderThenName($students)
            ->groupBy(fn (Student $student) => self::genderGroupKey($student->gender))
            ->sortKeysUsing(fn (string $a, string $b): int => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
    }

    /**
     * @param  Collection<string, Collection<int, Student>>  $groups
     */
    public static function showGenderHeader(string $genderKey, Collection $groups): bool
    {
        if (! $groups->has($genderKey)) {
            return false;
        }

        if ($genderKey === 'unspecified') {
            return $groups->has('male') || $groups->has('female');
        }

        return true;
    }

    public static function formatName(Student $student, string $style = 'full'): string
    {
        if ($style === 'formal') {
            $middle = $student->middle_name ? ' '.strtoupper(substr($student->middle_name, 0, 1)).'.' : '';

            return trim("{$student->last_name}, {$student->first_name}{$middle}");
        }

        if ($style === 'lastname_first') {
            $given = trim($student->first_name.' '.($student->middle_name ?? ''));

            return trim("{$student->last_name}, {$given}");
        }

        return $student->full_name;
    }
}
