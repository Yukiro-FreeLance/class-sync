<?php

namespace App\Livewire\Settings\Academic;

use App\Enums\Semester;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Departments & Grade Levels')]
class Structure extends Component
{
    public ?int $editingDepartmentId = null;

    public string $departmentName = '';

    public string $departmentCode = '';

    public int $departmentSortOrder = 0;

    public bool $departmentIsActive = true;

    public ?int $configuringDepartmentId = null;

    public bool $showSemesterModal = false;

    /** @var array<string, array{enabled: bool, label: string}> */
    public array $departmentSemesterConfig = [];

    public ?int $editingGradeId = null;

    public ?int $gradeDepartmentId = null;

    public string $gradeName = '';

    public string $gradeCode = '';

    public int $gradeSortOrder = 0;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    protected function defaultSemesterPayload(): array
    {
        return collect([Semester::First, Semester::Second])
            ->map(fn (Semester $semester) => [
                'code' => $semester->value,
                'label' => $semester->label(),
            ])
            ->values()
            ->all();
    }

    protected function initializeDepartmentSemesterConfig(Department $department): void
    {
        $this->departmentSemesterConfig = [];
        $savedEntries = collect($department->semesterEntries())->keyBy('code');

        foreach (Semester::cases() as $semester) {
            $saved = $savedEntries->get($semester->value);

            $this->departmentSemesterConfig[$semester->value] = [
                'enabled' => $saved !== null,
                'label' => $saved['label'] ?? $semester->label(),
            ];
        }
    }

    public function editDepartment(int $id): void
    {
        $department = Department::query()->findOrFail($id);
        $this->cancelSemesterConfig();
        $this->editingDepartmentId = $department->id;
        $this->departmentName = $department->name;
        $this->departmentCode = $department->code;
        $this->departmentSortOrder = $department->sort_order;
        $this->departmentIsActive = $department->is_active;
    }

    public function resetDepartmentForm(): void
    {
        $this->reset(['editingDepartmentId', 'departmentName', 'departmentCode', 'departmentSortOrder', 'departmentIsActive']);
        $this->departmentIsActive = true;
    }

    public function configureSemesters(int $id): void
    {
        $department = Department::query()->findOrFail($id);
        $this->resetDepartmentForm();
        $this->configuringDepartmentId = $department->id;
        $this->initializeDepartmentSemesterConfig($department);
        $this->showSemesterModal = true;
    }

    public function cancelSemesterConfig(): void
    {
        $this->configuringDepartmentId = null;
        $this->departmentSemesterConfig = [];
        $this->showSemesterModal = false;
    }

    public function resetSemesterLabels(): void
    {
        foreach (Semester::cases() as $semester) {
            if (isset($this->departmentSemesterConfig[$semester->value])) {
                $this->departmentSemesterConfig[$semester->value]['label'] = $semester->label();
            }
        }
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    protected function validatedSemesterPayload(): array
    {
        $this->validate([
            'departmentSemesterConfig' => ['required', 'array'],
            'departmentSemesterConfig.*.enabled' => ['boolean'],
            'departmentSemesterConfig.*.label' => ['nullable', 'string', 'max:80'],
        ]);

        $enabledSemesters = collect($this->departmentSemesterConfig)
            ->filter(fn (array $config) => $config['enabled'] ?? false);

        if ($enabledSemesters->isEmpty()) {
            $this->addError('departmentSemesterConfig', 'Enable at least one semester.');

            return [];
        }

        foreach ($enabledSemesters as $code => $config) {
            if (Semester::tryFrom((string) $code) === null) {
                continue;
            }

            if (trim((string) ($config['label'] ?? '')) === '') {
                $this->addError("departmentSemesterConfig.{$code}.label", 'Semester label is required when enabled.');

                return [];
            }
        }

        return $enabledSemesters
            ->map(fn (array $config, string $code) => [
                'code' => $code,
                'label' => trim($config['label']),
            ])
            ->values()
            ->all();
    }

    public function saveSemesters(): void
    {
        if (! $this->configuringDepartmentId) {
            return;
        }

        $semesters = $this->validatedSemesterPayload();

        if ($semesters === []) {
            return;
        }

        Department::query()
            ->whereKey($this->configuringDepartmentId)
            ->update(['semesters' => $semesters]);

        $this->dispatch('toast', message: 'Semesters saved.', type: 'success');
        $this->cancelSemesterConfig();
    }

    public function saveDepartment(): void
    {
        $this->validate([
            'departmentName' => ['required', 'string', 'max:100'],
            'departmentCode' => ['required', 'string', 'max:20', 'unique:departments,code,'.($this->editingDepartmentId ?? 'NULL')],
            'departmentSortOrder' => ['required', 'integer', 'min:0'],
            'departmentIsActive' => ['boolean'],
        ]);

        $payload = [
            'name' => $this->departmentName,
            'code' => strtolower($this->departmentCode),
            'sort_order' => $this->departmentSortOrder,
            'is_active' => $this->departmentIsActive,
        ];

        if ($this->editingDepartmentId) {
            Department::query()->whereKey($this->editingDepartmentId)->update($payload);
        } else {
            Department::query()->create(array_merge($payload, [
                'semesters' => $this->defaultSemesterPayload(),
            ]));
        }

        $this->resetDepartmentForm();
        $this->dispatch('toast', message: 'Department saved.', type: 'success');
    }

    public function deleteDepartment(int $id): void
    {
        if ($this->configuringDepartmentId === $id) {
            $this->cancelSemesterConfig();
        }

        Department::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Department removed.', type: 'success');
    }

    public function editGrade(int $id): void
    {
        $grade = GradeLevel::query()->findOrFail($id);
        $this->editingGradeId = $grade->id;
        $this->gradeDepartmentId = $grade->department_id;
        $this->gradeName = $grade->name;
        $this->gradeCode = $grade->code;
        $this->gradeSortOrder = $grade->sort_order;
    }

    public function resetGradeForm(): void
    {
        $this->reset(['editingGradeId', 'gradeDepartmentId', 'gradeName', 'gradeCode', 'gradeSortOrder']);
    }

    public function saveGrade(): void
    {
        $this->validate([
            'gradeDepartmentId' => ['required', 'exists:departments,id'],
            'gradeName' => ['required', 'string', 'max:100'],
            'gradeCode' => ['required', 'string', 'max:20', 'unique:grade_levels,code,'.($this->editingGradeId ?? 'NULL')],
            'gradeSortOrder' => ['required', 'integer', 'min:0'],
        ]);

        GradeLevel::query()->updateOrCreate(
            ['id' => $this->editingGradeId],
            [
                'department_id' => $this->gradeDepartmentId,
                'name' => $this->gradeName,
                'code' => $this->gradeCode,
                'sort_order' => $this->gradeSortOrder,
            ],
        );

        $this->resetGradeForm();
        $this->dispatch('toast', message: 'Grade level saved.', type: 'success');
    }

    public function deleteGrade(int $id): void
    {
        GradeLevel::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Grade level removed.', type: 'success');
    }

    public function render()
    {
        $configuringDepartment = $this->configuringDepartmentId
            ? Department::query()->find($this->configuringDepartmentId)
            : null;

        return view('livewire.settings.academic.structure', [
            'departments' => Department::query()->ordered()->withCount('gradeLevels')->get(),
            'grades' => GradeLevel::query()->with('department')->ordered()->get(),
            'semesterDefinitions' => Semester::cases(),
            'configuringDepartment' => $configuringDepartment,
        ]);
    }
}
