<?php

namespace App\Livewire\Settings\Academic;

use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Strands')]
class Strands extends Component
{
    #[Url]
    public string $grade = '';

    public ?int $editingId = null;

    public ?int $gradeLevelId = null;

    public string $name = '';

    public string $code = '';

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    public function edit(int $id): void
    {
        $course = Course::query()->findOrFail($id);
        $this->editingId = $course->id;
        $this->gradeLevelId = $course->grade_level_id;
        $this->name = $course->name;
        $this->code = $course->code;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'gradeLevelId', 'name', 'code']);
    }

    public function save(): void
    {
        $this->validate([
            'gradeLevelId' => ['required', 'exists:grade_levels,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $gradeLevel = GradeLevel::query()->with('department')->findOrFail($this->gradeLevelId);

        if (! $gradeLevel->isSeniorHigh()) {
            $this->addError('gradeLevelId', 'Strands are only available for Senior High School grades.');

            return;
        }

        $code = strtoupper($this->code);

        $exists = Course::query()
            ->where('grade_level_id', $this->gradeLevelId)
            ->where('code', $code)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($exists) {
            $this->addError('code', 'This strand code already exists for the selected grade.');

            return;
        }

        Course::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'grade_level_id' => $this->gradeLevelId,
                'name' => $this->name,
                'code' => $code,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: 'Strand saved.', type: 'success');
    }

    public function delete(int $id): void
    {
        Course::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Strand removed.', type: 'success');
    }

    public function render()
    {
        $shsGrades = GradeLevel::query()
            ->whereHas('department', fn ($q) => $q->where('code', 'shs'))
            ->ordered()
            ->get();

        $strands = Course::query()
            ->with('gradeLevel')
            ->whereHas('gradeLevel.department', fn ($q) => $q->where('code', 'shs'))
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->orderBy('grade_level_id')
            ->orderBy('code')
            ->get();

        return view('livewire.settings.academic.strands', [
            'grades' => $shsGrades,
            'strands' => $strands,
            'department' => Department::query()->where('code', 'shs')->first(),
        ]);
    }
}
