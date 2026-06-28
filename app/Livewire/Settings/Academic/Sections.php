<?php

namespace App\Livewire\Settings\Academic;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sections')]
class Sections extends Component
{
    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    public ?int $editingId = null;

    public ?int $gradeLevelId = null;

    public ?int $academicYearId = null;

    public string $name = '';

    public ?int $adviserId = null;

    public ?int $roomId = null;

    public string $room = '';

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
        $this->academicYearId ??= AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
    }

    public function edit(int $id): void
    {
        $section = Section::query()->findOrFail($id);
        $this->editingId = $section->id;
        $this->gradeLevelId = $section->grade_level_id;
        $this->academicYearId = $section->academic_year_id;
        $this->name = $section->name;
        $this->adviserId = $section->adviser_id;
        $this->roomId = $section->room_id;
        $this->room = $section->room ?? '';
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'gradeLevelId', 'name', 'adviserId', 'roomId', 'room']);
    }

    public function save(): void
    {
        $this->validate([
            'gradeLevelId' => ['required', 'exists:grade_levels,id'],
            'academicYearId' => ['nullable', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50'],
            'adviserId' => ['nullable', 'exists:users,id'],
            'roomId' => ['nullable', 'exists:rooms,id'],
            'room' => ['nullable', 'string', 'max:100'],
        ]);

        Section::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'grade_level_id' => $this->gradeLevelId,
                'academic_year_id' => $this->academicYearId,
                'name' => $this->name,
                'adviser_id' => $this->adviserId,
                'room_id' => $this->roomId,
                'room' => $this->room ?: null,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: 'Section saved.', type: 'success');
    }

    public function delete(int $id): void
    {
        Section::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Section removed.', type: 'success');
    }

    public function render()
    {
        $grades = GradeLevel::query()
            ->with('department')
            ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
            ->ordered()
            ->get();

        $sections = Section::query()
            ->with(['gradeLevel.department', 'adviser', 'assignedRoom', 'academicYear'])
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->department, function ($q) {
                $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department));
            })
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        return view('livewire.settings.academic.sections', [
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => $grades,
            'sections' => $sections,
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'rooms' => Room::query()->active()->orderBy('name')->get(),
            'teachers' => User::query()->assignableAsTeacher()->active()->orderBy('last_name')->get(),
        ]);
    }
}
