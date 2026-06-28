<?php

namespace App\Livewire\Concerns;

use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Services\Academic\TeacherScopeService;
use App\Services\Attendance\ClassScheduleResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

trait HasAttendanceClassFilters
{
    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $section = '';

    public string $date = '';

    public ?int $classScheduleId = null;

    public function bootAttendanceClassFilters(): void
    {
        $this->date = $this->date ?: Carbon::today()->toDateString();
    }

    public function updatedDepartment(): void
    {
        $this->grade = '';
        $this->section = '';
        $this->classScheduleId = null;
        $this->onAttendanceFiltersChanged();
    }

    public function updatedGrade(): void
    {
        $this->section = '';
        $this->classScheduleId = null;
        $this->onAttendanceFiltersChanged();
    }

    public function updatedSection(): void
    {
        $this->classScheduleId = null;
        $this->autoSelectClassSchedule();
        $this->onAttendanceFiltersChanged();
    }

    public function updatedDate(): void
    {
        $this->classScheduleId = null;
        $this->autoSelectClassSchedule();
        $this->onAttendanceFiltersChanged();
    }

    public function updatedClassScheduleId(): void
    {
        if ($this->classScheduleId && ! $this->isSelectedScheduleAllowed()) {
            $this->classScheduleId = null;
        }

        $this->onAttendanceFiltersChanged();
    }

    protected function onAttendanceFiltersChanged(): void
    {
        //
    }

    protected function autoPrefillDepartment(): void
    {
        if ($this->department !== '') {
            return;
        }

        $user = auth()->user();
        $teacherScope = $this->teacherScope();
        $isTeacherScoped = $this->isTeacherAttendanceScoped();

        $accessibleSectionIds = $user
            ? ($isTeacherScoped
                ? $teacherScope->accessibleScheduleSectionIds($user)
                : $teacherScope->accessibleSectionIds($user))
            : [];

        $department = Department::query()
            ->active()
            ->ordered()
            ->when($user && ! $teacherScope->bypassesScope($user), function ($query) use ($accessibleSectionIds) {
                $query->whereHas('gradeLevels.sections', fn ($sectionQuery) => $sectionQuery->whereIn('id', $accessibleSectionIds ?: [-1]));
            })
            ->first();

        if ($department) {
            $this->department = (string) $department->id;
        }
    }

    protected function teacherScope(): TeacherScopeService
    {
        return app(TeacherScopeService::class);
    }

    protected function isTeacherAttendanceScoped(): bool
    {
        $user = auth()->user();

        return $user ? $this->teacherScope()->isAttendanceScoped($user) : false;
    }

    protected function isSelectedScheduleAllowed(): bool
    {
        if (! $this->section || ! $this->classScheduleId) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $this->teacherScope()->canAccessClassSchedule(
            $user,
            $this->classScheduleId,
            (int) $this->section,
            $this->date,
        );
    }

    protected function autoSelectClassSchedule(): void
    {
        if (! $this->section) {
            return;
        }

        $user = auth()->user();
        $schedules = $user
            ? $this->teacherScope()->accessibleClassSchedules($user, (int) $this->section, $this->date)
            : app(ClassScheduleResolver::class)->forSectionOnDate((int) $this->section, $this->date);

        if ($schedules->isEmpty()) {
            $this->classScheduleId = null;

            return;
        }

        $suggested = app(ClassScheduleResolver::class)->suggestCurrent((int) $this->section, $this->date);

        if ($suggested && $schedules->contains('id', $suggested->id)) {
            $this->classScheduleId = $suggested->id;

            return;
        }

        $this->classScheduleId = $schedules->first()->id;
    }

    /**
     * @return Collection<int, ClassSchedule>
     */
    protected function classSchedulesForSelection(): Collection
    {
        if (! $this->section) {
            return collect();
        }

        $user = auth()->user();

        if ($user) {
            return $this->teacherScope()->accessibleClassSchedules($user, (int) $this->section, $this->date);
        }

        return app(ClassScheduleResolver::class)
            ->forSectionOnDate((int) $this->section, $this->date);
    }

    protected function assertCanManageClassAttendance(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->section || ! $this->classScheduleId) {
            abort(403);
        }

        $this->teacherScope()->authorizeClassAttendance(
            $user,
            $this->classScheduleId,
            (int) $this->section,
            $this->date,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function attendanceClassFilterData(): array
    {
        $teacherScope = $this->teacherScope();
        $user = auth()->user();
        $isTeacherScoped = $this->isTeacherAttendanceScoped();

        $accessibleSectionIds = $user
            ? ($isTeacherScoped
                ? $teacherScope->accessibleScheduleSectionIds($user)
                : $teacherScope->accessibleSectionIds($user))
            : [];

        $sectionsQuery = Section::query()
            ->when($this->grade, fn ($query) => $query->where('grade_level_id', $this->grade))
            ->when($this->department, fn ($query) => $query->whereHas(
                'gradeLevel',
                fn ($gradeQuery) => $gradeQuery->where('department_id', $this->department),
            ));

        if ($user && ! $teacherScope->bypassesScope($user)) {
            $sectionsQuery->whereIn('id', $accessibleSectionIds ?: [-1]);
        }

        $sections = $sectionsQuery->orderBy('name')->get();

        $grades = GradeLevel::query()
            ->when($this->department, fn ($query) => $query->where('department_id', $this->department))
            ->when($user && ! $teacherScope->bypassesScope($user), function ($query) use ($accessibleSectionIds) {
                $query->whereHas('sections', fn ($sectionQuery) => $sectionQuery->whereIn('id', $accessibleSectionIds ?: [-1]));
            })
            ->ordered()
            ->get();

        $departments = Department::query()
            ->active()
            ->ordered()
            ->when($user && ! $teacherScope->bypassesScope($user), function ($query) use ($accessibleSectionIds) {
                $query->whereHas('gradeLevels.sections', fn ($sectionQuery) => $sectionQuery->whereIn('id', $accessibleSectionIds ?: [-1]));
            })
            ->get();

        if ($isTeacherScoped && $this->section && ! in_array((int) $this->section, $accessibleSectionIds, true)) {
            $this->section = '';
            $this->classScheduleId = null;
        }

        if ($this->classScheduleId && ! $this->isSelectedScheduleAllowed()) {
            $this->classScheduleId = null;
        }

        return [
            'isTeacherScoped' => $isTeacherScoped,
            'departments' => $departments,
            'grades' => $grades,
            'sections' => $sections,
            'classSchedules' => $this->classSchedulesForSelection(),
            'selectedSchedule' => $this->classScheduleId
                ? $this->classSchedulesForSelection()->firstWhere('id', $this->classScheduleId)
                : null,
            'weekdayLabel' => Carbon::parse($this->date)->format('l'),
        ];
    }
}
