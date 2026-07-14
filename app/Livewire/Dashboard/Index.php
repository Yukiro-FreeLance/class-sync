<?php

namespace App\Livewire\Dashboard;

use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Services\Dashboard\DashboardService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    #[Url]
    public string $period = 'day';

    #[Url]
    public string $date = '';

    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $section = '';

    public bool $showDetailsModal = false;

    public string $detailsCategory = '';

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = now()->toDateString();
        }

        if (! in_array($this->period, ['day', 'week', 'month'], true)) {
            $this->period = 'day';
        }
    }

    public function updatedPeriod(): void
    {
        if (! in_array($this->period, ['day', 'week', 'month'], true)) {
            $this->period = 'day';
        }

        $this->closeStatusDetails();
    }

    public function updatedDate(): void
    {
        $this->closeStatusDetails();
    }

    public function updatedDepartment(): void
    {
        $this->reset(['grade', 'section']);
        $this->closeStatusDetails();
    }

    public function updatedGrade(): void
    {
        $this->reset(['section']);
        $this->closeStatusDetails();
    }

    public function updatedSection(): void
    {
        $this->closeStatusDetails();
    }

    public function clearFilters(): void
    {
        $this->period = 'day';
        $this->date = now()->toDateString();
        $this->reset(['department', 'grade', 'section']);
        $this->closeStatusDetails();
    }

    public function openStatusDetails(string $category): void
    {
        $allowed = [
            'attended', 'present', 'late', 'excused', 'absent',
            'half_day', 'not_recorded', 'visitors', 'checkouts', 'recorded',
        ];

        if (! in_array($category, $allowed, true)) {
            return;
        }

        $this->detailsCategory = $category;
        $this->showDetailsModal = true;
    }

    public function closeStatusDetails(): void
    {
        $this->showDetailsModal = false;
        $this->detailsCategory = '';
    }

    /**
     * @return array{department?: int, grade?: int, section?: int}
     */
    protected function filters(): array
    {
        return array_filter([
            'department' => $this->department !== '' ? (int) $this->department : null,
            'grade' => $this->grade !== '' ? (int) $this->grade : null,
            'section' => $this->section !== '' ? (int) $this->section : null,
        ], fn ($value) => $value !== null);
    }

    public function render(DashboardService $dashboard)
    {
        $referenceDate = Carbon::parse($this->date ?: now()->toDateString())->startOfDay();
        $filters = $this->filters();

        $data = $dashboard->data($referenceDate, $this->period, $filters);

        $statusDetails = null;

        if ($this->showDetailsModal && $this->detailsCategory !== '') {
            $statusDetails = $dashboard->statusDetails(
                $this->detailsCategory,
                $referenceDate,
                $this->period,
                $filters,
            );
        }

        return view('livewire.dashboard.index', array_merge($data, [
            'greeting' => $this->greeting(),
            'dashboardService' => $dashboard,
            'statusDetails' => $statusDetails,
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->ordered()
                ->get(),
            'sections' => Section::query()
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->orderBy('name')
                ->get(),
            'hasActiveFilters' => $this->department !== ''
                || $this->grade !== ''
                || $this->section !== ''
                || $this->period !== 'day'
                || $this->date !== now()->toDateString(),
        ]));
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }
}
