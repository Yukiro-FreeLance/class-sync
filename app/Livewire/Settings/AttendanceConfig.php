<?php

namespace App\Livewire\Settings;

use App\Models\AttendanceRemark;
use App\Models\ClassPeriod;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Attendance Configuration')]
class AttendanceConfig extends Component
{
    public string $tab = 'remarks';

    public ?int $editingRemarkId = null;

    public string $remarkCode = '';

    public string $remarkLabel = '';

    public string $remarkColor = '#6366f1';

    public bool $remarkCountsAsPresent = false;

    public bool $remarkIsDefault = false;

    public bool $remarkIsActive = true;

    public int $remarkSortOrder = 0;

    public ?int $editingPeriodId = null;

    public string $periodName = '';

    public string $periodCode = '';

    public string $periodStartsAt = '';

    public string $periodEndsAt = '';

    public int $periodSortOrder = 0;

    public bool $periodIsActive = true;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    public function editRemark(int $id): void
    {
        $remark = AttendanceRemark::query()->findOrFail($id);
        $this->editingRemarkId = $remark->id;
        $this->remarkCode = $remark->code;
        $this->remarkLabel = $remark->label;
        $this->remarkColor = $remark->color;
        $this->remarkCountsAsPresent = $remark->counts_as_present;
        $this->remarkIsDefault = $remark->is_default;
        $this->remarkIsActive = $remark->is_active;
        $this->remarkSortOrder = $remark->sort_order;
    }

    public function resetRemarkForm(): void
    {
        $this->reset(['editingRemarkId', 'remarkCode', 'remarkLabel', 'remarkColor', 'remarkCountsAsPresent', 'remarkIsDefault', 'remarkIsActive', 'remarkSortOrder']);
        $this->remarkColor = '#6366f1';
        $this->remarkIsActive = true;
    }

    public function saveRemark(): void
    {
        $this->validate([
            'remarkCode' => ['required', 'string', 'max:50', 'unique:attendance_remarks,code,'.($this->editingRemarkId ?? 'NULL')],
            'remarkLabel' => ['required', 'string', 'max:100'],
            'remarkColor' => ['required', 'string', 'max:20'],
            'remarkSortOrder' => ['integer', 'min:0'],
        ]);

        if ($this->remarkIsDefault) {
            AttendanceRemark::query()->update(['is_default' => false]);
        }

        AttendanceRemark::query()->updateOrCreate(
            ['id' => $this->editingRemarkId],
            [
                'code' => strtolower($this->remarkCode),
                'label' => $this->remarkLabel,
                'color' => $this->remarkColor,
                'counts_as_present' => $this->remarkCountsAsPresent,
                'is_default' => $this->remarkIsDefault,
                'is_active' => $this->remarkIsActive,
                'sort_order' => $this->remarkSortOrder,
            ],
        );

        $this->resetRemarkForm();
        $this->dispatch('toast', message: 'Attendance remark saved.', type: 'success');
    }

    public function deleteRemark(int $id): void
    {
        AttendanceRemark::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Remark removed.', type: 'success');
    }

    public function editPeriod(int $id): void
    {
        $period = ClassPeriod::query()->findOrFail($id);
        $this->editingPeriodId = $period->id;
        $this->periodName = $period->name;
        $this->periodCode = $period->code;
        $this->periodStartsAt = $period->starts_at ? substr((string) $period->starts_at, 0, 5) : '';
        $this->periodEndsAt = $period->ends_at ? substr((string) $period->ends_at, 0, 5) : '';
        $this->periodSortOrder = $period->sort_order;
        $this->periodIsActive = $period->is_active;
    }

    public function resetPeriodForm(): void
    {
        $this->reset(['editingPeriodId', 'periodName', 'periodCode', 'periodStartsAt', 'periodEndsAt', 'periodSortOrder', 'periodIsActive']);
        $this->periodIsActive = true;
    }

    public function savePeriod(): void
    {
        $this->validate([
            'periodName' => ['required', 'string', 'max:100'],
            'periodCode' => ['required', 'string', 'max:50', 'unique:class_periods,code,'.($this->editingPeriodId ?? 'NULL')],
            'periodStartsAt' => ['nullable', 'date_format:H:i'],
            'periodEndsAt' => ['nullable', 'date_format:H:i'],
            'periodSortOrder' => ['integer', 'min:0'],
        ]);

        ClassPeriod::query()->updateOrCreate(
            ['id' => $this->editingPeriodId],
            [
                'name' => $this->periodName,
                'code' => strtolower($this->periodCode),
                'starts_at' => $this->periodStartsAt ?: null,
                'ends_at' => $this->periodEndsAt ?: null,
                'sort_order' => $this->periodSortOrder,
                'is_active' => $this->periodIsActive,
            ],
        );

        $this->resetPeriodForm();
        $this->dispatch('toast', message: 'Class period saved.', type: 'success');
    }

    public function deletePeriod(int $id): void
    {
        ClassPeriod::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Period removed.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.attendance-config', [
            'remarks' => AttendanceRemark::query()->ordered()->get(),
            'periods' => ClassPeriod::query()->ordered()->get(),
        ]);
    }
}
