<?php

namespace App\Livewire\Settings\Academic;

use App\Models\AcademicYear;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Academic Years')]
class Years extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $startDate = '';

    public string $endDate = '';

    public bool $isCurrent = false;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    public function edit(int $id): void
    {
        $year = AcademicYear::query()->findOrFail($id);
        $this->editingId = $year->id;
        $this->name = $year->name;
        $this->startDate = $year->start_date->toDateString();
        $this->endDate = $year->end_date->toDateString();
        $this->isCurrent = $year->is_current;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'startDate', 'endDate', 'isCurrent']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name,'.($this->editingId ?? 'NULL')],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'isCurrent' => ['boolean'],
        ]);

        if ($this->isCurrent) {
            AcademicYear::query()->update(['is_current' => false]);
        }

        AcademicYear::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'is_current' => $this->isCurrent,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: 'Academic year saved.', type: 'success');
    }

    public function setCurrent(int $id): void
    {
        AcademicYear::query()->update(['is_current' => false]);
        AcademicYear::query()->whereKey($id)->update(['is_current' => true]);
        $this->dispatch('toast', message: 'Current academic year updated.', type: 'success');
    }

    public function delete(int $id): void
    {
        AcademicYear::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Academic year removed.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.academic.years', [
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }
}
