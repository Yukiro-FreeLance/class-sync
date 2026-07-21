<?php

namespace App\Livewire\Settings\Academic;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\ClassScheduleConflictService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    #[Url]
    public string $strand = '';

    public ?int $academicYearId = null;

    public string $semester = 'first';

    public ?int $editingId = null;

    public ?int $sectionId = null;

    public ?int $formCourseId = null;

    public ?int $subjectId = null;

    public ?int $teacherId = null;

    public ?int $roomId = null;

    public string $defaultStartsAt = '08:00';

    public string $defaultEndsAt = '09:00';

    public bool $showConflictsOnly = false;

    public string $timeMode = 'same';

    public string $viewMode = 'list';

    /** @var array<int, bool> */
    public array $expandedDays = [];

    /** @var array<int, array{enabled: bool, times: list<array{starts_at: string, ends_at: string}>}> */
    public array $daySlots = [];

    /** @var list<array{starts_at: string, ends_at: string}> */
    public array $sharedTimeSlots = [];

    public ?string $quickAddPanel = null;

    public ?int $quickSectionGradeLevelId = null;

    public ?int $quickSectionCourseId = null;

    public string $quickSectionName = '';

    public ?int $quickSubjectDepartmentId = null;

    public string $quickSubjectName = '';

    public string $quickSubjectCode = '';

    public string $quickTeacherFirstName = '';

    public string $quickTeacherLastName = '';

    public string $quickTeacherUsername = '';

    public string $quickTeacherEmail = '';

    public string $quickRoomName = '';

    public string $quickRoomCode = '';

    public string $quickRoomBuilding = '';

    public ?int $quickStrandGradeLevelId = null;

    public string $quickStrandName = '';

    public string $quickStrandCode = '';

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
        $this->academicYearId ??= AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
        $this->ensureValidSemester();
        $this->initializeDaySlots();
        $this->sharedTimeSlots = [$this->defaultTimeSlot()];
        $this->expandAll();
    }

    public function resetFilters(): void
    {
        $this->department = '';
        $this->grade = '';
        $this->strand = '';
        $this->semester = Semester::First->value;
        $this->academicYearId = AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
        $this->showConflictsOnly = false;
        $this->ensureValidSemester();
    }

    public function updatedTimeMode(): void
    {
        if ($this->timeMode === 'same') {
            $this->applyDefaultTimes();
        }
    }

    public function toggleDay(int $day): void
    {
        if ($this->editingId) {
            foreach ($this->daySlots as $value => $slot) {
                $this->daySlots[$value]['enabled'] = $value === $day
                    ? ! ($slot['enabled'] ?? false)
                    : false;
            }

            if ($this->daySlots[$day]['enabled'] ?? false) {
                $this->daySlots[$day]['times'] = [$this->defaultTimeSlot()];
            }

            return;
        }

        $enabled = ! ($this->daySlots[$day]['enabled'] ?? false);
        $this->daySlots[$day]['enabled'] = $enabled;

        if ($enabled) {
            $this->ensureDayHasTimeSlot($day);
        } else {
            $this->daySlots[$day]['times'] = [];
        }
    }

    public function expandAll(): void
    {
        foreach (DayOfWeek::cases() as $day) {
            $this->expandedDays[$day->value] = true;
        }
    }

    public function collapseAll(): void
    {
        foreach (DayOfWeek::cases() as $day) {
            $this->expandedDays[$day->value] = false;
        }
    }

    public function toggleDayPanel(int $day): void
    {
        $this->expandedDays[$day] = ! ($this->expandedDays[$day] ?? false);
    }

    public function addClassForDay(int $day): void
    {
        $this->resetForm();
        $this->daySlots[$day]['enabled'] = true;
        $this->daySlots[$day]['times'] = [$this->defaultTimeSlot()];
        $this->dispatch('scroll-to-schedule-form');
    }

    public function copyLastSchedule(): void
    {
        if ($this->editingId) {
            return;
        }

        $lastSchedule = ClassSchedule::query()
            ->with(['section.gradeLevel'])
            ->when($this->academicYearId, fn ($q) => $q->where('academic_year_id', $this->academicYearId))
            ->when($this->semester, fn ($q) => $q->where('semester', $this->semester))
            ->latest('updated_at')
            ->first();

        if (! $lastSchedule) {
            $this->dispatch('toast', message: 'No previous schedule to copy.', type: 'warning');

            return;
        }

        $this->sectionId = $lastSchedule->section_id;
        $this->subjectId = $lastSchedule->subject_id;
        $this->teacherId = $lastSchedule->teacher_id;
        $this->roomId = $lastSchedule->room_id;
        $this->defaultStartsAt = substr((string) $lastSchedule->starts_at, 0, 5);
        $this->defaultEndsAt = substr((string) $lastSchedule->ends_at, 0, 5);

        foreach ($this->daySlots as $value => $slot) {
            $this->daySlots[$value]['enabled'] = false;
        }

        $this->daySlots[$lastSchedule->day_of_week->value]['enabled'] = true;
        $this->daySlots[$lastSchedule->day_of_week->value]['times'] = [[
            'starts_at' => $this->defaultStartsAt,
            'ends_at' => $this->defaultEndsAt,
        ]];

        $this->dispatch('toast', message: 'Last schedule copied to form.', type: 'success');
    }

    public function showComingSoon(string $feature): void
    {
        $this->dispatch('toast', message: "{$feature} is coming soon.", type: 'info');
    }

    public function updatedDepartment(): void
    {
        $this->grade = '';
        $this->strand = '';
        $this->formCourseId = null;
        $this->sectionId = null;
        $this->ensureValidSemester();
    }

    public function updatedGrade(): void
    {
        $this->strand = '';
        $this->formCourseId = null;
        $this->sectionId = null;
    }

    public function updatedStrand(): void
    {
        $this->formCourseId = $this->strand ? (int) $this->strand : null;

        if ($this->sectionId) {
            $section = Section::query()->find($this->sectionId);

            if ($this->strand && (int) ($section?->course_id) !== (int) $this->strand) {
                $this->sectionId = null;
            }
        }
    }

    public function updatedFormCourseId(): void
    {
        if ($this->sectionId) {
            $section = Section::query()->find($this->sectionId);

            if ((int) ($section?->course_id) !== (int) $this->formCourseId) {
                $this->sectionId = null;
            }
        }
    }

    public function updatedSectionId(): void
    {
        if ($this->sectionId) {
            $section = Section::query()->with('course')->find($this->sectionId);

            if ($section?->course_id) {
                $this->formCourseId = $section->course_id;
                $this->strand = (string) $section->course_id;
            }
        }

        $options = $this->semesterOptionsForSection($this->sectionId);

        if ($options !== [] && ! array_key_exists($this->semester, $options)) {
            $this->semester = array_key_first($options) ?? Semester::First->value;
        }
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

    public function updatedDefaultStartsAt(): void
    {
        if ($this->timeMode === 'same') {
            $this->applyDefaultTimes();
        }
    }

    public function updatedDefaultEndsAt(): void
    {
        if ($this->timeMode === 'same') {
            $this->applyDefaultTimes();
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
                $this->ensureDayHasTimeSlot($day);
            }

            return;
        }

        if ($this->daySlots[$day]['enabled'] ?? false) {
            $this->ensureDayHasTimeSlot($day);
        }
    }

    public function addSharedTimeSlot(): void
    {
        $this->sharedTimeSlots[] = $this->defaultTimeSlot();
    }

    public function removeSharedTimeSlot(int $index): void
    {
        if (count($this->sharedTimeSlots) <= 1) {
            return;
        }

        unset($this->sharedTimeSlots[$index]);
        $this->sharedTimeSlots = array_values($this->sharedTimeSlots);
    }

    public function addDayTimeSlot(int $day): void
    {
        if (! ($this->daySlots[$day]['enabled'] ?? false)) {
            return;
        }

        $this->daySlots[$day]['times'][] = $this->defaultTimeSlot();
    }

    public function removeDayTimeSlot(int $day, int $index): void
    {
        if (! ($this->daySlots[$day]['enabled'] ?? false)) {
            return;
        }

        $times = $this->daySlots[$day]['times'] ?? [];

        if (count($times) <= 1) {
            return;
        }

        unset($times[$index]);
        $this->daySlots[$day]['times'] = array_values($times);
    }

    protected function defaultTimeSlot(): array
    {
        return [
            'starts_at' => $this->defaultStartsAt,
            'ends_at' => $this->defaultEndsAt,
        ];
    }

    protected function ensureDayHasTimeSlot(int $day): void
    {
        if (($this->daySlots[$day]['times'] ?? []) === []) {
            $this->daySlots[$day]['times'] = [$this->defaultTimeSlot()];
        }
    }

    protected function initializeDaySlots(?ClassSchedule $schedule = null): void
    {
        foreach (DayOfWeek::cases() as $day) {
            $isActiveDay = $schedule && $schedule->day_of_week === $day;

            $this->daySlots[$day->value] = [
                'enabled' => $isActiveDay,
                'times' => $isActiveDay
                    ? [[
                        'starts_at' => substr((string) $schedule->starts_at, 0, 5),
                        'ends_at' => substr((string) $schedule->ends_at, 0, 5),
                    ]]
                    : [],
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
                $this->ensureDayHasTimeSlot($day);
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
        if ($this->timeMode === 'same') {
            if ($this->sharedTimeSlots === []) {
                $this->sharedTimeSlots = [$this->defaultTimeSlot()];
            } else {
                $this->sharedTimeSlots[0]['starts_at'] = $this->defaultStartsAt;
                $this->sharedTimeSlots[0]['ends_at'] = $this->defaultEndsAt;
            }

            return;
        }

        foreach ($this->daySlots as $day => $slot) {
            if ($slot['enabled'] && ($slot['times'][0] ?? null)) {
                $this->daySlots[$day]['times'][0]['starts_at'] = $this->defaultStartsAt;
                $this->daySlots[$day]['times'][0]['ends_at'] = $this->defaultEndsAt;
            }
        }
    }

    public function edit(int $id): void
    {
        $schedule = ClassSchedule::query()->with('section.course')->findOrFail($id);
        $this->editingId = $schedule->id;
        $this->academicYearId = $schedule->academic_year_id;
        $this->sectionId = $schedule->section_id;
        $this->subjectId = $schedule->subject_id;
        $this->teacherId = $schedule->teacher_id;
        $this->roomId = $schedule->room_id;
        $this->semester = $schedule->semester->value;
        $this->formCourseId = $schedule->section?->course_id;
        $this->defaultStartsAt = substr((string) $schedule->starts_at, 0, 5);
        $this->defaultEndsAt = substr((string) $schedule->ends_at, 0, 5);
        $this->timeMode = 'same';
        $this->sharedTimeSlots = [[
            'starts_at' => $this->defaultStartsAt,
            'ends_at' => $this->defaultEndsAt,
        ]];
        $this->initializeDaySlots($schedule);
        $this->dispatch('scroll-to-schedule-form');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'sectionId', 'formCourseId', 'subjectId', 'teacherId', 'roomId']);
        $this->defaultStartsAt = '08:00';
        $this->defaultEndsAt = '09:00';
        $this->sharedTimeSlots = [$this->defaultTimeSlot()];
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

        return $this->conflictService()->findConflictsForFormEntries(
            $this->academicYearId,
            $this->sectionId,
            $this->subjectId,
            $this->teacherId,
            $this->roomId,
            $this->semester,
            $this->enabledTimeEntries()->all(),
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

        $timeEntries = $this->enabledTimeEntries();

        if ($timeEntries->isEmpty()) {
            $this->addError('daySlots', 'Select at least one day and time slot.');

            return;
        }

        foreach ($timeEntries as $entry) {
            validator(
                ['starts_at' => $entry['starts_at'], 'ends_at' => $entry['ends_at']],
                [
                    'starts_at' => ['required', 'date_format:H:i'],
                    'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
                ],
            )->validate();
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
            $entry = $timeEntries->first();

            ClassSchedule::query()->whereKey($this->editingId)->update(array_merge($payload, [
                'day_of_week' => $entry['day'],
                'starts_at' => $entry['starts_at'],
                'ends_at' => $entry['ends_at'],
            ]));

            $message = 'Schedule updated.';
        } else {
            $saved = 0;

            foreach ($timeEntries as $entry) {
                ClassSchedule::query()->create(array_merge($payload, [
                    'day_of_week' => $entry['day'],
                    'starts_at' => $entry['starts_at'],
                    'ends_at' => $entry['ends_at'],
                ]));

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
     * @return Collection<int, array{day: int, starts_at: string, ends_at: string}>
     */
    protected function enabledTimeEntries(): Collection
    {
        $entries = collect();

        if ($this->timeMode === 'same') {
            $times = collect($this->sharedTimeSlots)
                ->filter(fn (array $time) => ($time['starts_at'] ?? '') !== '' && ($time['ends_at'] ?? '') !== '');

            foreach ($this->daySlots as $day => $slot) {
                if (! ($slot['enabled'] ?? false)) {
                    continue;
                }

                foreach ($times as $time) {
                    $entries->push([
                        'day' => (int) $day,
                        'starts_at' => $time['starts_at'],
                        'ends_at' => $time['ends_at'],
                    ]);
                }
            }

            return $entries;
        }

        foreach ($this->daySlots as $day => $slot) {
            if (! ($slot['enabled'] ?? false)) {
                continue;
            }

            foreach ($slot['times'] ?? [] as $time) {
                if (($time['starts_at'] ?? '') === '' || ($time['ends_at'] ?? '') === '') {
                    continue;
                }

                $entries->push([
                    'day' => (int) $day,
                    'starts_at' => $time['starts_at'],
                    'ends_at' => $time['ends_at'],
                ]);
            }
        }

        return $entries;
    }

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

    /** @param  list<int>  $ids */
    public function deleteGroup(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return;
        }

        $count = ClassSchedule::query()->whereIn('id', $ids)->delete();

        $message = $count === 1
            ? 'Schedule removed.'
            : "{$count} schedule entries removed.";

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @param  array<int, true>  $conflictScheduleIds
     * @param  array<int, list<string>>  $conflictDetails
     * @return Collection<int, array{
     *     schedule: ClassSchedule,
     *     schedules: Collection<int, ClassSchedule>,
     *     schedule_ids: list<int>,
     *     day_values: list<int>,
     *     days_label: string,
     *     count: int,
     *     has_conflict: bool,
     *     conflicts: list<string>,
     *     is_editing: bool
     * }>
     */
    protected function buildScheduleGroups(
        Collection $schedules,
        array $conflictScheduleIds,
        array $conflictDetails,
    ): Collection {
        return $schedules
            ->groupBy(fn (ClassSchedule $schedule) => implode('|', [
                $schedule->subject_id,
                $schedule->section_id,
                $schedule->teacher_id,
                $schedule->room_id ?? 'none',
                substr((string) $schedule->starts_at, 0, 5),
                substr((string) $schedule->ends_at, 0, 5),
            ]))
            ->map(function (Collection $group) use ($conflictScheduleIds, $conflictDetails) {
                $sorted = $group
                    ->sortBy(fn (ClassSchedule $schedule) => $schedule->day_of_week->value)
                    ->values();

                $representative = $sorted->first();
                $dayValues = $sorted
                    ->pluck('day_of_week')
                    ->map(fn (DayOfWeek $day) => $day->value)
                    ->all();

                $scheduleIds = $sorted->pluck('id')->all();
                $conflicts = [];

                foreach ($scheduleIds as $id) {
                    if (isset($conflictScheduleIds[$id])) {
                        $conflicts = array_merge($conflicts, $conflictDetails[$id] ?? []);
                    }
                }

                return [
                    'schedule' => $representative,
                    'schedules' => $sorted,
                    'schedule_ids' => $scheduleIds,
                    'day_values' => $dayValues,
                    'days_label' => $this->formatGroupedDaysLabel($dayValues),
                    'count' => $sorted->count(),
                    'has_conflict' => $conflicts !== [],
                    'conflicts' => array_values(array_unique($conflicts)),
                    'is_editing' => $sorted->contains(fn (ClassSchedule $schedule) => $schedule->id === $this->editingId),
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => ($a['schedule']->subject?->code ?? '') <=> ($b['schedule']->subject?->code ?? ''),
                fn (array $a, array $b) => substr((string) $a['schedule']->starts_at, 0, 5) <=> substr((string) $b['schedule']->starts_at, 0, 5),
                fn (array $a, array $b) => min($a['day_values']) <=> min($b['day_values']),
            ])
            ->values();
    }

    /** @param  list<int>  $dayValues */
    protected function formatGroupedDaysLabel(array $dayValues): string
    {
        sort($dayValues);

        if ($dayValues === []) {
            return '';
        }

        if (count($dayValues) === 1) {
            return DayOfWeek::from($dayValues[0])->label();
        }

        $ranges = [];
        $rangeStart = $dayValues[0];
        $previous = $dayValues[0];

        for ($index = 1; $index < count($dayValues); $index++) {
            $current = $dayValues[$index];

            if ($current === $previous + 1) {
                $previous = $current;

                continue;
            }

            $ranges[] = [$rangeStart, $previous];
            $rangeStart = $current;
            $previous = $current;
        }

        $ranges[] = [$rangeStart, $previous];

        $parts = [];

        foreach ($ranges as [$start, $end]) {
            if ($start === $end) {
                $parts[] = DayOfWeek::from($start)->shortLabel();
            } else {
                $parts[] = DayOfWeek::from($start)->shortLabel().'–'.DayOfWeek::from($end)->shortLabel();
            }
        }

        return implode(', ', $parts);
    }

    public function openQuickAdd(string $panel): void
    {
        $this->resetQuickAddForms();
        $this->quickAddPanel = $panel;
        $this->prefillQuickAdd($panel);
    }

    public function closeQuickAdd(): void
    {
        $this->quickAddPanel = null;
        $this->resetQuickAddForms();
        $this->resetValidation();
    }

    public function updatedQuickSectionGradeLevelId(): void
    {
        $this->quickSectionCourseId = null;
    }

    public function saveQuickStrand(): void
    {
        $this->validate([
            'quickStrandGradeLevelId' => ['required', 'exists:grade_levels,id'],
            'quickStrandName' => ['required', 'string', 'max:150'],
            'quickStrandCode' => ['required', 'string', 'max:20'],
        ]);

        $gradeLevel = GradeLevel::query()->with('department')->findOrFail($this->quickStrandGradeLevelId);

        if (! $gradeLevel->isSeniorHigh()) {
            $this->addError('quickStrandGradeLevelId', 'Strands are only available for Senior High School grades.');

            return;
        }

        $code = strtoupper($this->quickStrandCode);

        $course = Course::query()->updateOrCreate(
            [
                'grade_level_id' => $this->quickStrandGradeLevelId,
                'code' => $code,
            ],
            [
                'name' => $this->quickStrandName,
            ],
        );

        $this->formCourseId = $course->id;
        $this->strand = (string) $course->id;
        $this->closeQuickAdd();
        $this->dispatch('toast', message: 'Strand saved and selected.', type: 'success');
    }

    public function saveQuickSection(): void
    {
        $rules = [
            'quickSectionGradeLevelId' => ['required', 'exists:grade_levels,id'],
            'quickSectionName' => ['required', 'string', 'max:50'],
            'quickSectionCourseId' => ['nullable', 'exists:courses,id'],
        ];

        $gradeLevel = GradeLevel::query()->with('department')->findOrFail($this->quickSectionGradeLevelId);

        if ($gradeLevel->isSeniorHigh()) {
            $rules['quickSectionCourseId'] = ['required', 'exists:courses,id'];
        }

        $this->validate($rules);

        $section = Section::query()->create([
            'grade_level_id' => $this->quickSectionGradeLevelId,
            'course_id' => $gradeLevel->isSeniorHigh() ? $this->quickSectionCourseId : null,
            'academic_year_id' => $this->academicYearId,
            'name' => $this->quickSectionName,
        ]);

        $this->sectionId = $section->id;

        if ($section->course_id) {
            $this->formCourseId = $section->course_id;
            $this->strand = (string) $section->course_id;
        }

        $this->closeQuickAdd();
        $this->dispatch('toast', message: 'Section saved and selected.', type: 'success');
    }

    public function saveQuickSubject(): void
    {
        $this->validate([
            'quickSubjectDepartmentId' => ['nullable', 'exists:departments,id'],
            'quickSubjectName' => ['required', 'string', 'max:150'],
            'quickSubjectCode' => ['required', 'string', 'max:50', 'unique:subjects,code'],
        ]);

        $subject = Subject::query()->create([
            'department_id' => $this->quickSubjectDepartmentId,
            'name' => $this->quickSubjectName,
            'code' => strtoupper($this->quickSubjectCode),
            'is_active' => true,
        ]);

        $this->subjectId = $subject->id;
        $this->closeQuickAdd();
        $this->dispatch('toast', message: 'Subject saved and selected.', type: 'success');
    }

    public function saveQuickTeacher(): void
    {
        $this->validate([
            'quickTeacherFirstName' => ['required', 'string', 'max:100'],
            'quickTeacherLastName' => ['required', 'string', 'max:100'],
            'quickTeacherUsername' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'quickTeacherEmail' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ]);

        $username = Str::lower($this->quickTeacherUsername);
        $email = $this->quickTeacherEmail
            ? Str::lower($this->quickTeacherEmail)
            : "{$username}@school.local";

        if (User::query()->where('email', $email)->exists()) {
            $email = "{$username}+".Str::lower(Str::random(4)).'@school.local';
        }

        $teacher = User::query()->create([
            'name' => trim("{$this->quickTeacherFirstName} {$this->quickTeacherLastName}"),
            'first_name' => $this->quickTeacherFirstName,
            'last_name' => $this->quickTeacherLastName,
            'username' => $username,
            'email' => $email,
            'password' => Str::password(12),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $teacher->assignRole(UserRole::Teacher->value);

        $this->teacherId = $teacher->id;
        $this->closeQuickAdd();
        $this->dispatch('toast', message: 'Teacher saved and selected.', type: 'success');
    }

    public function saveQuickRoom(): void
    {
        $this->validate([
            'quickRoomName' => ['required', 'string', 'max:100'],
            'quickRoomCode' => ['nullable', 'string', 'max:50'],
            'quickRoomBuilding' => ['nullable', 'string', 'max:100'],
        ]);

        $room = Room::query()->create([
            'name' => $this->quickRoomName,
            'code' => $this->quickRoomCode ? strtoupper($this->quickRoomCode) : null,
            'building' => $this->quickRoomBuilding ?: null,
            'is_active' => true,
        ]);

        $this->roomId = $room->id;
        $this->closeQuickAdd();
        $this->dispatch('toast', message: 'Room saved and selected.', type: 'success');
    }

    protected function prefillQuickAdd(string $panel): void
    {
        match ($panel) {
            'strand' => $this->quickStrandGradeLevelId = $this->grade ? (int) $this->grade : null,
            'section' => $this->prefillQuickSection(),
            'subject' => $this->quickSubjectDepartmentId = $this->sectionDepartmentId(),
            default => null,
        };
    }

    protected function prefillQuickSection(): void
    {
        if ($this->grade) {
            $this->quickSectionGradeLevelId = (int) $this->grade;
        } elseif ($this->sectionId) {
            $this->quickSectionGradeLevelId = Section::query()
                ->whereKey($this->sectionId)
                ->value('grade_level_id');
        }

        $this->quickSectionCourseId = $this->formCourseId;
    }

    protected function resetQuickAddForms(): void
    {
        $this->reset([
            'quickSectionGradeLevelId',
            'quickSectionCourseId',
            'quickSectionName',
            'quickSubjectDepartmentId',
            'quickSubjectName',
            'quickSubjectCode',
            'quickTeacherFirstName',
            'quickTeacherLastName',
            'quickTeacherUsername',
            'quickTeacherEmail',
            'quickRoomName',
            'quickRoomCode',
            'quickRoomBuilding',
            'quickStrandGradeLevelId',
            'quickStrandName',
            'quickStrandCode',
        ]);
    }

    public function render()
    {
        $sections = Section::query()
            ->with(['gradeLevel.department', 'course'])
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->department, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
            ->when($this->strand, fn ($q) => $q->where('course_id', $this->strand))
            ->when($this->formCourseId && ! $this->strand, fn ($q) => $q->where('course_id', $this->formCourseId))
            ->orderBy('grade_level_id')
            ->orderBy('course_id')
            ->orderBy('name')
            ->get();

        $departmentId = $this->sectionDepartmentId();
        $showStrandFilter = $this->isSeniorHighFilterContext();

        $strands = Course::query()
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->department && ! $this->grade, function ($q) {
                $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department));
            })
            ->orderBy('grade_level_id')
            ->orderBy('code')
            ->get();

        $schedules = ClassSchedule::query()
            ->with(['section.gradeLevel', 'section.course', 'subject', 'teacher', 'room', 'academicYear'])
            ->when($this->academicYearId, fn ($q) => $q->where('academic_year_id', $this->academicYearId))
            ->when($this->semester, fn ($q) => $q->where('semester', $this->semester))
            ->when($this->grade, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_level_id', $this->grade)))
            ->when($this->department, fn ($q) => $q->whereHas('section.gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
            ->when($this->strand, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('course_id', $this->strand)))
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
        $schedulesByDay = $schedules->groupBy(fn (ClassSchedule $schedule) => $schedule->day_of_week->value);
        $scheduleGroups = $this->buildScheduleGroups($schedules, $conflictScheduleIds, $conflictDetails);
        $stats = $this->buildScheduleStats($schedules);
        $daySummaries = $this->buildDaySummaries($schedulesByDay);

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
            'scheduleGroups' => $scheduleGroups,
            'days' => DayOfWeek::options(),
            'semesters' => $this->availableSemesterOptions(),
            'formSemesters' => $this->formSemesterOptions(),
            'selectedDayCount' => $this->enabledTimeEntries()->count(),
            'formConflicts' => $formConflicts,
            'conflictScheduleIds' => $conflictScheduleIds,
            'conflictDetails' => $conflictDetails,
            'conflictCount' => count($conflictScheduleIds),
            'schedulesByDay' => $schedulesByDay,
            'stats' => $stats,
            'daySummaries' => $daySummaries,
            'strands' => $strands,
            'showStrandFilter' => $showStrandFilter,
            'showFormStrandField' => $showStrandFilter || $this->formCourseId,
            'shsGrades' => GradeLevel::query()
                ->whereHas('department', fn ($q) => $q->where('code', 'shs'))
                ->ordered()
                ->get(),
            'quickSectionIsShs' => $this->quickSectionGradeLevelId
                ? (GradeLevel::query()->with('department')->find($this->quickSectionGradeLevelId)?->isSeniorHigh() ?? false)
                : false,
            'quickSectionStrands' => Course::query()
                ->when($this->quickSectionGradeLevelId, fn ($q) => $q->where('grade_level_id', $this->quickSectionGradeLevelId))
                ->orderBy('code')
                ->get(),
        ]);
    }

    protected function isSeniorHighFilterContext(): bool
    {
        if ($this->department) {
            return Department::query()
                ->whereKey($this->department)
                ->where('code', 'shs')
                ->exists();
        }

        if ($this->grade) {
            return GradeLevel::query()
                ->whereKey($this->grade)
                ->whereHas('department', fn ($q) => $q->where('code', 'shs'))
                ->exists();
        }

        return false;
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array{
     *     total_classes: int,
     *     weekly_minutes: int,
     *     weekly_hours_label: string,
     *     teachers: int,
     *     subjects: int,
     *     rooms_used: int
     * }
     */
    protected function buildScheduleStats(Collection $schedules): array
    {
        $weeklyMinutes = $schedules->sum(fn (ClassSchedule $schedule) => $this->scheduleDurationMinutes($schedule));

        return [
            'total_classes' => $schedules->count(),
            'weekly_minutes' => $weeklyMinutes,
            'weekly_hours_label' => $this->formatDurationLabel($weeklyMinutes),
            'teachers' => $schedules->pluck('teacher_id')->unique()->count(),
            'subjects' => $schedules->pluck('subject_id')->unique()->count(),
            'rooms_used' => $schedules->pluck('room_id')->filter()->unique()->count(),
        ];
    }

    /**
     * @param  Collection<int, Collection<int, ClassSchedule>>  $schedulesByDay
     * @return array<int, array{count: int, minutes: int, label: string}>
     */
    protected function buildDaySummaries(Collection $schedulesByDay): array
    {
        $summaries = [];

        foreach (DayOfWeek::cases() as $day) {
            $daySchedules = $schedulesByDay->get($day->value, collect());
            $minutes = $daySchedules->sum(fn (ClassSchedule $schedule) => $this->scheduleDurationMinutes($schedule));

            $summaries[$day->value] = [
                'count' => $daySchedules->count(),
                'minutes' => $minutes,
                'label' => $this->formatDurationLabel($minutes),
            ];
        }

        return $summaries;
    }

    protected function scheduleDurationMinutes(ClassSchedule $schedule): int
    {
        $start = Carbon::parse((string) $schedule->starts_at);
        $end = Carbon::parse((string) $schedule->ends_at);

        return max(0, $start->diffInMinutes($end));
    }

    protected function formatDurationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes === 0) {
            return "{$hours}h 00m";
        }

        return sprintf('%dh %02dm', $hours, $remainingMinutes);
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
