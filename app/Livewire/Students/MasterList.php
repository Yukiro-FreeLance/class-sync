<?php

namespace App\Livewire\Students;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Services\Students\StudentListService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Master List')]
class MasterList extends Component
{
    #[Url]
    public ?int $academicYearId = null;

    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $section = '';

    public bool $activeOnly = true;

    #[Url]
    public string $gender = '';

    public function mount(StudentListService $listService): void
    {
        $this->authorize('viewAny', Student::class);
        $this->academicYearId ??= $listService->currentAcademicYearId();
    }

    public function updatedDepartment(): void
    {
        $this->reset(['grade', 'section']);
    }

    public function updatedGrade(): void
    {
        $this->reset(['section']);
    }

    public function exportUrl(string $format = 'xlsx'): string
    {
        return route('students.lists.master.export', array_filter([
            'format' => $format,
            'academic_year_id' => $this->academicYearId,
            'department' => $this->department ?: null,
            'grade' => $this->grade ?: null,
            'section' => $this->section ?: null,
            'active_only' => $this->activeOnly ? '1' : '0',
            'gender' => $this->gender ?: null,
        ]));
    }

    public function render(StudentListService $listService)
    {
        $user = auth()->user();

        $groupedStudents = collect();
        $totalCount = 0;
        $gradeLevel = $this->grade ? GradeLevel::query()->with('department')->find($this->grade) : null;

        if ($this->academicYearId && $this->grade) {
            $groupedStudents = $listService->masterListGrouped(
                $this->academicYearId,
                (int) $this->grade,
                $this->section ? (int) $this->section : null,
                $this->activeOnly,
                $user,
                $this->gender ?: null,
            );
            $totalCount = $groupedStudents->flatten(1)->count();
        }

        $academicYear = $this->academicYearId
            ? AcademicYear::query()->find($this->academicYearId)
            : null;

        return view('livewire.students.master-list', [
            'groupedStudents' => $groupedStudents,
            'totalCount' => $totalCount,
            'gradeLevel' => $gradeLevel,
            'academicYear' => $academicYear,
            'school' => $listService->schoolContext(),
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->ordered()
                ->get(),
            'sections' => Section::query()
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canExport' => $this->grade && $totalCount > 0,
            'genderFilters' => StudentListService::genderFilterOptions(),
        ]);
    }
}
