<?php

namespace App\Livewire\Reports;

use App\DTOs\Reports\ReportPreview;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Services\Reports\ReportService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Reports')]
class Index extends Component
{
    #[Url]
    public string $reportType = 'attendance_summary';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $section = '';

    public function mount(ReportService $reportService): void
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->toDateString();
        }

        if ($this->dateTo === '') {
            $this->dateTo = now()->toDateString();
        }
    }

    public function updatedDepartment(): void
    {
        $this->reset(['grade', 'section']);
    }

    public function updatedGrade(): void
    {
        $this->reset(['section']);
    }

    public function exportUrl(string $format): string
    {
        return route('reports.export', array_filter([
            'report_type' => $this->reportType,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'department' => $this->department ?: null,
            'grade' => $this->grade ?: null,
            'section' => $this->section ?: null,
            'format' => $format,
        ]));
    }

    /**
     * @return array{department?: int, grade?: int, section?: int}
     */
    protected function filters(): array
    {
        return array_filter([
            'department' => $this->department ? (int) $this->department : null,
            'grade' => $this->grade ? (int) $this->grade : null,
            'section' => $this->section ? (int) $this->section : null,
        ], fn ($value) => $value !== null);
    }

    protected function preview(ReportService $reportService): ReportPreview
    {
        $this->validate([
            'reportType' => ['required', 'string', 'in:'.implode(',', array_keys($reportService->reportTypes()))],
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        return $reportService->preview(
            $this->reportType,
            $this->dateFrom,
            $this->dateTo,
            $this->filters(),
        );
    }

    public function render(ReportService $reportService)
    {
        $preview = null;
        $previewError = null;

        try {
            $preview = $this->preview($reportService);
        } catch (ValidationException $e) {
            $previewError = collect($e->errors())->flatten()->first();
        }

        $displayRows = $preview ? array_slice($preview->rows, 0, 100) : [];
        $truncated = $preview && count($preview->rows) > 100;

        return view('livewire.reports.index', [
            'reportTypes' => $reportService->reportTypes(),
            'schoolName' => $reportService->schoolName(),
            'preview' => $preview,
            'previewError' => $previewError,
            'displayRows' => $displayRows,
            'truncated' => $truncated,
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->ordered()
                ->get(),
            'sections' => Section::query()
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->orderBy('name')
                ->get(),
            'canExport' => auth()->user()?->can('reports.export') ?? false,
        ]);
    }
}
