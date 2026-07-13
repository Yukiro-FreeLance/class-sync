<?php

namespace App\Livewire\Students;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Students\StudentListService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Class List')]
class ClassList extends Component
{
    #[Url]
    public ?int $academicYearId = null;

    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $section = '';

    #[Url]
    public string $subject = '';

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
        $this->reset(['grade', 'section', 'subject']);
    }

    public function updatedGrade(): void
    {
        $this->reset(['section', 'subject']);
    }

    public function updatedSection(): void
    {
        $this->reset(['subject']);
    }

    public function exportUrl(string $format = 'xlsx'): string
    {
        return route('students.lists.class.export', array_filter([
            'format' => $format,
            'academic_year_id' => $this->academicYearId,
            'section' => $this->section ?: null,
            'subject' => $this->subject ?: null,
            'active_only' => $this->activeOnly ? '1' : '0',
            'gender' => $this->gender ?: null,
        ]));
    }

    public function render(StudentListService $listService)
    {
        $user = auth()->user();

        $students = collect();
        $sectionContext = null;
        $selectedSubject = null;

        if ($this->academicYearId && $this->section) {
            $students = $listService->classListQuery(
                $this->academicYearId,
                (int) $this->section,
                $this->subject ? (int) $this->subject : null,
                $this->activeOnly,
                $user,
                $this->gender ?: null,
            )->get();

            $sectionContext = $listService->sectionContext((int) $this->section, $this->academicYearId);
            $selectedSubject = $this->subject ? Subject::query()->find($this->subject) : null;
        }

        $subjects = collect();
        if ($this->academicYearId && $this->section) {
            $subjectIds = ClassSchedule::query()
                ->where('academic_year_id', $this->academicYearId)
                ->where('section_id', $this->section)
                ->distinct()
                ->pluck('subject_id');

            $subjects = Subject::query()->whereIn('id', $subjectIds)->orderBy('name')->get();
        }

        $academicYear = $this->academicYearId
            ? AcademicYear::query()->find($this->academicYearId)
            : null;

        return view('livewire.students.class-list', [
            'students' => $students,
            'sectionContext' => $sectionContext,
            'selectedSubject' => $selectedSubject,
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
            'subjects' => $subjects,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canExport' => $this->section && $students->isNotEmpty(),
            'genderFilters' => StudentListService::genderFilterOptions(),
        ]);
    }
}
