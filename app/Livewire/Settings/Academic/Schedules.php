<?php

namespace App\Livewire\Settings\Academic;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\ClassScheduleConflictService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Class Schedules')]
class Schedules extends Component
{
    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    public ?int $academicYearId = null;

    public string $semester = 'first';

    public ?int $editingId = null;

    public ?int $sectionId = null;

    public ?int $subjectId = null;

    public ?int $teacherId = null;

    public ?int $roomId = null;

    public string $defaultStartsAt = '08:00';

    public string $defaultEndsAt = '09:00';

    public bool $showConflictsOnly = false;

    /** @var array<int, array{enabled: bool, starts_at: string, ends_at: string}> */
    public array $daySlots = [];

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
        $this->academicYearId ??= AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
        $this->ensureValidSemester();
        $this->initializeDaySlots();
    }

    public function updatedDepartment(): void
    {
        $this->grade = '';
        $this->ensureValidSemester();
    }

    protected function ensureValidSemester(): void
    {
        $options = $this->availableSemesterOptions();

        if ($options === [] || ! array_key_exists($this->semester, $options)) {
            $this->semester = array_key_first($options) ?? Semester::First->value;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function availableSemesterOptions(): array
    {
        if ($this->department) {
            $department = Department::query()->find($this->department);

            return $department?->semesterOptions() ?? Semester::options();
        }

        $values = Department::query()
            ->active()
            ->pluck('semesters')
            ->flatMap(fn ($semesters) => is_array($semesters) && $semesters !== []
                ? $semesters
                : Semester::defaultValues())
            ->unique()
            ->values()
            ->all();

        return Semester::optionsForValues($values);
    }

    protected function semesterOptionsForSection(?int $sectionId): array
    {
        if (! $sectionId) {
            return Semester::options();
        }

        $section = Section::query()->with('gradeLevel.department')->find($sectionId);

        return $section?->gradeLevel?->department?->semesterOptions() ?? Semester::options();
    }

    protected function formSemesterOptions(): array
    {
        if ($this->sectionId) {
            return $this->semesterOptionsForSection($this->sectionId);
        }

        return $this->availableSemesterOptions();
    }

    public function updatedSectionId(): void
    {
        $options = $this->semesterOptionsForSection($this->sectionId);

        if ($options !== [] && ! array_key_exists($this->semester, $options)) {
            $this->semester = array_key_first($options) ?? Semester::First->value;
        }
    }

    public function updated($property): void
    {
        if (! preg_match('/^daySlots\.(\d+)\.enabled$/', $property, $matches)) {
            return;
        }

        $day = (int) $matches[1];

        if ($this->editingId) {
            foreach ($this->daySlots as $value => $slot) {
                if ($value !== $day) {
                    $this->daySlots[$value]['enabled'] = false;
                }
            }

            if ($this->daySlots[$day]['enabled'] ?? false) {
                $this->daySlots[$day]['starts_at'] = $this->defaultStartsAt;
                $this->daySlots[$day]['ends_at'] = $this->defaultEndsAt;
            }

            return;
        }

        if ($this->daySlots[$day]['enabled'] ?? false) {
            $this->daySlots[$day]['starts_at'] = $this->defaultStartsAt;
            $this->daySlots[$day]['ends_at'] = $this->defaultEndsAt;
        }
    }

    protected function initializeDaySlots(?ClassSchedule $schedule = null): void
    {
        foreach (DayOfWeek::cases() as $day) {
            $isActiveDay = $schedule && $schedule->day_of_week === $day;

            $this->daySlots[$day->value] = [
                'enabled' => $isActiveDay,
                'starts_at' => $isActiveDay
                    ? substr((string) $schedule->starts_at, 0, 5)
                    : $this->defaultStartsAt,
                'ends_at' => $isActiveDay
                    ? substr((string) $schedule->ends_at, 0, 5)
                    : $this->defaultEndsAt,
            ];
        }
    }

    public function selectWeekdays(): void
    {
        if ($this->editingId) {
            return;
        }

        foreach ($this->daySlots as $day => $slot) {
            $enabled = $day >= DayOfWeek::Monday->value && $day <= DayOfWeek::Friday->value;
            $this->daySlots[$day]['enabled'] = $enabled;

            if ($enabled) {
                $this->daySlots[$day]['starts_at'] = $this->defaultStartsAt;
                $this->daySlots[$day]['ends_at'] = $this->defaultEndsAt;
            }
        }
    }

    public function clearDays(): void
    {
        if ($this->editingId) {
            return;
        }

        foreach ($this->daySlots as $day => $slot) {
            $this->daySlots[$day]['enabled'] = false;
        }
    }

    public function applyDefaultTimes(): void
    {
        foreach ($this->daySlots as $day => $slot) {
            if ($slot['enabled']) {
                $this->daySlots[$day]['starts_at'] = $this->defaultStartsAt;
                $this->daySlots[$day]['ends_at'] = $this->defaultEndsAt;
            }
        }
    }

    public function edit(int $id): void
    {
        $schedule = ClassSchedule::query()->findOrFail($id);
        $this->editingId = $schedule->id;
        $this->academicYearId = $schedule->academic_year_id;
        $this->sectionId = $schedule->section_id;
        $this->subjectId = $schedule->subject_id;
        $this->teacherId = $schedule->teacher_id;
        $this->roomId = $schedule->room_id;
        $this->semester = $schedule->semester->value;
        $this->defaultStartsAt = substr((string) $schedule->starts_at, 0, 5);
        $this->defaultEndsAt = substr((string) $schedule->ends_at, 0, 5);
        $this->initializeDaySlots($schedule);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'sectionId', 'subjectId', 'teacherId', 'roomId']);
        $this->defaultStartsAt = '08:00';
        $this->defaultEndsAt = '09:00';
        $this->initializeDaySlots();
    }

    protected function conflictService(): ClassScheduleConflictService
    {
        return app(ClassScheduleConflictService::class);
    }

    /**
     * @return list<array{type: string, message: string, schedule_id: int, day: int}>
     */
    protected function previewFormConflicts(): array
    {
        if (! $this->academicYearId || ! $this->sectionId || ! $this->subjectId || ! $this->teacherId) {
            return [];
        }

        return $this->conflictService()->findConflictsForForm(
            $this->academicYearId,
            $this->sectionId,
            $this->subjectId,
            $this->teacherId,
            $this->roomId,
            $this->semester,
            $this->daySlots,
            $this->editingId,
        );
    }

    public function save(): void
    {
        $section = Section::query()->with('gradeLevel.department')->findOrFail($this->sectionId);
        $allowedSemesters = array_keys($section->gradeLevel?->department?->semesterOptions() ?? Semester::options());

        $this->validate([
            'academicYearId' => ['required', 'exists:academic_years,id'],
            'sectionId' => ['required', 'exists:sections,id'],
            'subjectId' => ['required', 'exists:subjects,id'],
            'teacherId' => ['required', 'exists:users,id'],
            'roomId' => ['nullable', 'exists:rooms,id'],
            'semester' => ['required', 'in:'.implode(',', $allowedSemesters)],
            'defaultStartsAt' => ['required', 'date_format:H:i'],
            'defaultEndsAt' => ['required', 'date_format:H:i', 'after:defaultStartsAt'],
            'daySlots' => ['required', 'array'],
        ]);

        $enabledDays = $this->enabledDaySlots();

        if ($enabledDays->isEmpty()) {
            $this->addError('daySlots', 'Select at least one day.');

            return;
        }

        foreach ($enabledDays as $day => $slot) {
            $this->validate([
                "daySlots.{$day}.starts_at" => ['required', 'date_format:H:i'],
                "daySlots.{$day}.ends_at" => ['required', 'date_format:H:i', 'after:daySlots.'.$day.'.starts_at'],
            ]);
        }

        $conflicts = $this->previewFormConflicts();

        if ($conflicts !== []) {
            $this->addError('conflicts', collect($conflicts)->pluck('message')->unique()->implode(' '));

            return;
        }

        $payload = [
            'academic_year_id' => $this->academicYearId,
            'section_id' => $this->sectionId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacherId,
            'room_id' => $this->roomId,
            'semester' => $this->semester,
        ];

        if ($this->editingId) {
            $day = $enabledDays->keys()->first();
            $slot = $enabledDays->first();

            ClassSchedule::query()->whereKey($this->editingId)->update(array_merge($payload, [
                'day_of_week' => $day,
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
            ]));

            $message = 'Schedule updated.';
        } else {
            $saved = 0;

            foreach ($enabledDays as $day => $slot) {
                ClassSchedule::query()->updateOrCreate(
                    [
                        'academic_year_id' => $this->academicYearId,
                        'section_id' => $this->sectionId,
                        'subject_id' => $this->subjectId,
                        'semester' => $this->semester,
                        'day_of_week' => $day,
                    ],
                    array_merge($payload, [
                        'starts_at' => $slot['starts_at'],
                        'ends_at' => $slot['ends_at'],
                    ]),
                );

                $saved++;
            }

            $message = $saved === 1
                ? 'Schedule saved.'
                : "Saved {$saved} schedule entries.";
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    /**
     * @return Collection<int, array{enabled: bool, starts_at: string, ends_at: string}>
     */
    protected function enabledDaySlots(): Collection
    {
        return collect($this->daySlots)
            ->filter(fn (array $slot) => $slot['enabled']);
    }

    public function delete(int $id): void
    {
        ClassSchedule::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Schedule removed.', type: 'success');
    }

    public function render()
    {
        $sections = Section::query()
            ->with('gradeLevel.department')
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->department, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $departmentId = $this->sectionDepartmentId();

        $schedules = ClassSchedule::query()
            ->with(['section.gradeLevel', 'subject', 'teacher', 'room', 'academicYear'])
            ->when($this->academicYearId, fn ($q) => $q->where('academic_year_id', $this->academicYearId))
            ->when($this->semester, fn ($q) => $q->where('semester', $this->semester))
            ->when($this->grade, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_level_id', $this->grade)))
            ->when($this->department, fn ($q) => $q->whereHas('section.gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        $conflictAnalysis = $this->conflictService()->analyzeCollection($schedules);
        $conflictScheduleIds = $conflictAnalysis['ids'];
        $conflictDetails = $conflictAnalysis['details'];

        if ($this->showConflictsOnly) {
            $schedules = $schedules->filter(fn (ClassSchedule $schedule) => isset($conflictScheduleIds[$schedule->id]));
        }

        $formConflicts = $this->previewFormConflicts();

        return view('livewire.settings.academic.schedules', [
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->ordered()
                ->get(),
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'sections' => $sections,
            'subjects' => Subject::query()
                ->active()
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->orderBy('name')
                ->get(),
            'teachers' => User::query()->assignableAsTeacher()->active()->orderBy('last_name')->get(),
            'rooms' => Room::query()->active()->orderBy('name')->get(),
            'schedules' => $schedules,
            'days' => DayOfWeek::options(),
            'semesters' => $this->availableSemesterOptions(),
            'formSemesters' => $this->formSemesterOptions(),
            'selectedDayCount' => $this->enabledDaySlots()->count(),
            'formConflicts' => $formConflicts,
            'conflictScheduleIds' => $conflictScheduleIds,
            'conflictDetails' => $conflictDetails,
            'conflictCount' => count($conflictScheduleIds),
        ]);
    }

    protected function sectionDepartmentId(): ?int
    {
        if ($this->sectionId) {
            return Section::query()
                ->with('gradeLevel')
                ->find($this->sectionId)
                ?->gradeLevel
                ?->department_id;
        }

        if ($this->department) {
            return (int) $this->department;
        }

        return null;
    }
}
