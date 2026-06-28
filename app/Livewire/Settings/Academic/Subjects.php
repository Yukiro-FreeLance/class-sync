<?php

namespace App\Livewire\Settings\Academic;

use App\Models\Department;
use App\Models\Setting;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Subjects')]
class Subjects extends Component
{
    #[Url]
    public string $department = '';

    public ?int $editingId = null;

    public ?int $departmentId = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    public function edit(int $id): void
    {
        $subject = Subject::query()->findOrFail($id);
        $this->editingId = $subject->id;
        $this->departmentId = $subject->department_id;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->description = $subject->description ?? '';
        $this->isActive = $subject->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'departmentId', 'name', 'code', 'description', 'isActive']);
        $this->isActive = true;
    }

    public function save(): void
    {
        $this->validate([
            'departmentId' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code,'.($this->editingId ?? 'NULL')],
            'description' => ['nullable', 'string', 'max:500'],
            'isActive' => ['boolean'],
        ]);

        Subject::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'department_id' => $this->departmentId,
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'description' => $this->description ?: null,
                'is_active' => $this->isActive,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: 'Subject saved.', type: 'success');
    }

    public function delete(int $id): void
    {
        Subject::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Subject removed.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.academic.subjects', [
            'departments' => Department::query()->active()->ordered()->get(),
            'subjects' => Subject::query()
                ->with('department')
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->orderBy('name')
                ->get(),
        ]);
    }
}
