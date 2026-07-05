<?php

namespace App\Livewire\Students;

use App\DTOs\Students\BulkEnrollmentResult;
use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Services\Students\StudentEnrollmentService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bulk Enrollment')]
class BulkEnroll extends Component
{
    #[Url]
    public string $mode = 'section';

    public ?int $academicYearId = null;

    #[Url]
    public string $department = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $strand = '';

    #[Url]
    public string $section = '';

    public string $semesterFilter = '';

    public string $studentScope = 'grade';

    public string $status = 'enrolled';

    public ?string $enrollmentDate = null;

    public string $remarks = '';

    /** @var array<int> */
    public array $selectedStudentIds = [];

    /** @var array<int> */
    public array $selectedSubjectIds = [];

    public string $studentSearch = '';

    public bool $mergeExistingSubjects = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('students.update'), 403);

        $this->academicYearId = AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
        $this->enrollmentDate = now()->toDateString();

        if (! in_array($this->mode, ['section', 'subjects'], true)) {
            $this->mode = 'section';
        }
    }

    public function updatedMode(): void
    {
        $this->reset(['selectedStudentIds', 'selectedSubjectIds', 'studentSearch', 'studentScope']);
        $this->studentScope = $this->mode === 'subjects' ? 'section' : 'grade';
    }

    public function updatedDepartment(): void
    {
        $this->reset(['grade', 'strand', 'section', 'selectedStudentIds', 'selectedSubjectIds']);
    }

    public function updatedGrade(): void
    {
        $this->reset(['strand', 'section', 'selectedStudentIds', 'selectedSubjectIds']);
    }

    public function updatedStrand(): void
    {
        $this->reset(['section', 'selectedStudentIds', 'selectedSubjectIds']);
    }

    public function updatedSection(): void
    {
        $this->reset(['selectedStudentIds', 'selectedSubjectIds']);
        $this->autoSelectSubjects();
    }

    public function updatedAcademicYearId(): void
    {
        $this->reset(['selectedStudentIds', 'selectedSubjectIds']);
        $this->autoSelectSubjects();
    }

    public function updatedSemesterFilter(): void
    {
        $this->reset(['selectedSubjectIds']);
        $this->autoSelectSubjects();
    }

    public function updatedStudentScope(): void
    {
        $this->reset(['selectedStudentIds']);
    }

    protected function autoSelectSubjects(): void
    {
        if (! $this->section || ! $this->academicYearId) {
            $this->selectedSubjectIds = [];

            return;
        }

        $this->selectedSubjectIds = $this->enrollmentService()
            ->availableSubjectsGrouped((int) $this->section, $this->academicYearId, $this->semesterFilter ?: null)
            ->pluck('subject_id')
            ->all();
    }

    public function selectAllStudents(): void
    {
        $this->selectedStudentIds = $this->filteredStudents()->pluck('id')->all();
    }

    public function clearStudents(): void
    {
        $this->selectedStudentIds = [];
    }

    public function selectAllSubjects(): void
    {
        $this->selectedSubjectIds = $this->subjectGroups()->pluck('subject_id')->all();
    }

    public function clearSubjects(): void
    {
        $this->selectedSubjectIds = [];
    }

    public function enrollBulk(StudentEnrollmentService $enrollmentService): void
    {
        $this->validate([
            'academicYearId' => ['required', 'exists:academic_years,id'],
            'grade' => ['required', 'exists:grade_levels,id'],
            'section' => ['required', 'exists:sections,id'],
            'selectedStudentIds' => ['required', 'array', 'min:1'],
            'selectedStudentIds.*' => ['integer', 'exists:students,id'],
            'selectedSubjectIds' => ['required', 'array', 'min:1'],
            'status' => ['required', 'in:'.implode(',', array_keys(EnrollmentStatus::options()))],
            'enrollmentDate' => ['nullable', 'date'],
        ]);

        $scheduleIds = $enrollmentService->scheduleIdsForSubjects(
            (int) $this->section,
            $this->academicYearId,
            $this->selectedSubjectIds,
            $this->semesterFilter ?: null,
        );

        if ($scheduleIds === []) {
            $this->addError('selectedSubjectIds', 'No class schedules found for the selected subjects.');

            return;
        }

        $result = $enrollmentService->bulkEnroll($this->selectedStudentIds, [
            'academic_year_id' => $this->academicYearId,
            'grade_level_id' => (int) $this->grade,
            'section_id' => (int) $this->section,
            'status' => $this->status,
            'enrollment_date' => $this->enrollmentDate,
            'remarks' => $this->remarks ?: null,
            'class_schedule_ids' => $scheduleIds,
        ]);

        $this->dispatchResultToast($result, 'enrolled');
        $this->reset(['selectedStudentIds', 'remarks']);
    }

    public function assignSubjects(StudentEnrollmentService $enrollmentService): void
    {
        $this->validate([
            'academicYearId' => ['required', 'exists:academic_years,id'],
            'section' => ['required', 'exists:sections,id'],
            'selectedStudentIds' => ['required', 'array', 'min:1'],
            'selectedStudentIds.*' => ['integer', 'exists:students,id'],
            'selectedSubjectIds' => ['required', 'array', 'min:1'],
        ]);

        $scheduleIds = $enrollmentService->scheduleIdsForSubjects(
            (int) $this->section,
            $this->academicYearId,
            $this->selectedSubjectIds,
            $this->semesterFilter ?: null,
        );

        if ($scheduleIds === []) {
            $this->addError('selectedSubjectIds', 'No class schedules found for the selected subjects.');

            return;
        }

        $result = $enrollmentService->bulkAssignSubjects(
            $this->selectedStudentIds,
            $this->academicYearId,
            (int) $this->section,
            $scheduleIds,
            $this->mergeExistingSubjects,
        );

        $this->dispatchResultToast($result, 'updated');
    }

    protected function dispatchResultToast(BulkEnrollmentResult $result, string $verb): void
    {
        $message = "Successfully {$verb} {$result->successCount()} student(s).";

        if ($result->failed > 0) {
            $message .= " {$result->failed} failed.";
        }

        $this->dispatch(
            'toast',
            message: $message,
            type: $result->failed > 0 ? 'warning' : 'success',
        );
    }

    protected function enrollmentService(): StudentEnrollmentService
    {
        return app(StudentEnrollmentService::class);
    }

    /**
     * @return Collection<int, object{subject_id: int, subject: ?Subject, schedules: Collection}>
     */
    protected function subjectGroups(): Collection
    {
        if (! $this->section || ! $this->academicYearId) {
            return collect();
        }

        return $this->enrollmentService()->availableSubjectsGrouped(
            (int) $this->section,
            $this->academicYearId,
            $this->semesterFilter ?: null,
        );
    }

    protected function filteredStudents(): Collection
    {
        if (! $this->academicYearId) {
            return collect();
        }

        $query = Student::query()
            ->with(['gradeLevel', 'section.course'])
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->department, fn ($q) => $q->whereHas(
                'gradeLevel',
                fn ($gradeQuery) => $gradeQuery->where('department_id', $this->department),
            ));

        if ($this->mode === 'subjects') {
            if (! $this->section) {
                return collect();
            }

            $enrolledIds = StudentEnrollment::query()
                ->where('academic_year_id', $this->academicYearId)
                ->where('section_id', $this->section)
                ->pluck('student_id');

            $query->whereIn('id', $enrolledIds ?: [-1]);
        } else {
            match ($this->studentScope) {
                'unassigned' => $query->where(function ($q) {
                    $q->whereNull('section_id')
                        ->orWhereDoesntHave('enrollments', fn ($enrollmentQuery) => $enrollmentQuery
                            ->where('academic_year_id', $this->academicYearId));
                }),
                'section' => $this->section
                    ? $query->where('section_id', $this->section)
                    : $query->whereRaw('1 = 0'),
                default => $query->when($this->section, fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull('section_id')
                        ->orWhere('section_id', '!=', $this->section)
                        ->orWhereDoesntHave('enrollments', fn ($enrollmentQuery) => $enrollmentQuery
                            ->where('academic_year_id', $this->academicYearId)
                            ->where('section_id', $this->section));
                })),
            };
        }

        if ($this->studentSearch !== '') {
            $needle = mb_strtolower($this->studentSearch);
            $query->where(function ($q) use ($needle) {
                $q->where('student_number', 'like', "%{$needle}%")
                    ->orWhere('first_name', 'like', "%{$needle}%")
                    ->orWhere('last_name', 'like', "%{$needle}%");
            });
        }

        return $query
            ->with([
                'gradeLevel',
                'section',
                'enrollments' => fn ($q) => $q
                    ->where('academic_year_id', $this->academicYearId)
                    ->with('classSchedules'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    protected function semesterOptions(): array
    {
        if ($this->department) {
            return Department::query()->find($this->department)?->semesterOptions() ?? Semester::options();
        }

        if ($this->grade) {
            return GradeLevel::query()->with('department')->find($this->grade)?->department?->semesterOptions()
                ?? Semester::options();
        }

        return Semester::options();
    }

    public function render()
    {
        $students = $this->filteredStudents();
        $subjectGroups = $this->subjectGroups();
        $selectedSection = $this->section
            ? Section::query()->with(['gradeLevel.department', 'course'])->find($this->section)
            : null;

        return view('livewire.students.bulk-enroll', [
            'students' => $students,
            'subjectGroups' => $subjectGroups,
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->ordered()
                ->get(),
            'strands' => Course::query()
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->when($this->department && ! $this->grade, function ($q) {
                    $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department));
                })
                ->orderBy('grade_level_id')
                ->orderBy('code')
                ->get(),
            'showStrandFilter' => $this->isSeniorHighFilterContext(),
            'sections' => Section::query()
                ->with(['course', 'gradeLevel'])
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->when($this->strand, fn ($q) => $q->where('course_id', $this->strand))
                ->when($this->academicYearId, fn ($q) => $q->where(function ($query) {
                    $query->where('academic_year_id', $this->academicYearId)
                        ->orWhereNull('academic_year_id');
                }))
                ->orderBy('course_id')
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'semesterOptions' => $this->semesterOptions(),
            'statuses' => EnrollmentStatus::options(),
            'selectedSection' => $selectedSection,
            'scheduleCount' => $this->section && $this->selectedSubjectIds
                ? count($this->enrollmentService()->scheduleIdsForSubjects(
                    (int) $this->section,
                    $this->academicYearId,
                    $this->selectedSubjectIds,
                    $this->semesterFilter ?: null,
                ))
                : 0,
        ]);
    }

    protected function isSeniorHighFilterContext(): bool
    {
        if ($this->department) {
            return Department::query()
                ->whereKey($this->department)
                ->where('code', 'shs')
                ->exists();
        }

        if ($this->grade) {
            return GradeLevel::query()
                ->whereKey($this->grade)
                ->whereHas('department', fn ($q) => $q->where('code', 'shs'))
                ->exists();
        }

        return false;
    }
}
