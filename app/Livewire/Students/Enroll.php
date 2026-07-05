<?php

namespace App\Livewire\Students;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Students\StudentEnrollmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Enroll extends Component
{
    public Student $student;

    public ?int $academic_year_id = null;

    public ?int $department_id = null;

    public ?int $grade_level_id = null;

    public ?int $section_id = null;

    public ?int $course_id = null;

    public string $status = 'enrolled';

    public ?string $enrollment_date = null;

    public string $remarks = '';

    public string $semester_filter = '';

    /** @var array<int> */
    public array $class_schedule_ids = [];

    public function mount(Student $student): void
    {
        $this->authorize('enroll', $student);

        $this->student = $student;
        $this->academic_year_id = AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
        $this->enrollment_date = now()->toDateString();

        $this->loadExistingEnrollment();
    }

    public function updatedAcademicYearId(): void
    {
        $this->loadExistingEnrollment();
    }

    public function updatedDepartmentId(): void
    {
        $this->grade_level_id = null;
        $this->section_id = null;
        $this->course_id = null;
        $this->class_schedule_ids = [];
        $this->ensureValidSemesterFilter();
    }

    public function updatedGradeLevelId(): void
    {
        $this->section_id = null;
        $this->course_id = null;
        $this->class_schedule_ids = [];
        $this->ensureValidSemesterFilter();
    }

    public function updatedSectionId(): void
    {
        if ($this->section_id) {
            $section = Section::query()->with('gradeLevel.department', 'course')->find($this->section_id);

            if ($section?->gradeLevel?->department?->code === 'shs' && $section->course_id) {
                $this->course_id = $section->course_id;
            } else {
                $this->course_id = null;
            }
        } else {
            $this->course_id = null;
        }

        $this->ensureValidSemesterFilter();

        if ($this->section_id && $this->academic_year_id) {
            $this->class_schedule_ids = app(StudentEnrollmentService::class)
                ->defaultClassScheduleIds($this->section_id, $this->academic_year_id);
        } else {
            $this->class_schedule_ids = [];
        }
    }

    public function selectAllClasses(): void
    {
        $this->class_schedule_ids = $this->availableClasses()->pluck('id')->all();
    }

    public function clearClasses(): void
    {
        $this->class_schedule_ids = [];
    }

    public function save(StudentEnrollmentService $enrollmentService): void
    {
        $this->authorize('enroll', $this->student);

        $validated = $this->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(EnrollmentStatus::options()))],
            'enrollment_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'class_schedule_ids' => ['array'],
            'class_schedule_ids.*' => ['integer', 'exists:class_schedules,id'],
        ]);

        $enrollmentService->enroll($this->student, $validated);

        $this->dispatch('toast', message: 'Student enrollment saved successfully.', type: 'success');
        $this->redirect(route('students.show', $this->student), navigate: true);
    }

    protected function loadExistingEnrollment(): void
    {
        if (! $this->academic_year_id) {
            return;
        }

        $enrollment = StudentEnrollment::query()
            ->where('student_id', $this->student->id)
            ->where('academic_year_id', $this->academic_year_id)
            ->with('classSchedules')
            ->first();

        if (! $enrollment) {
            $this->department_id = $this->student->gradeLevel?->department_id;
            $this->grade_level_id = $this->student->grade_level_id;
            $this->section_id = $this->student->section_id;
            $this->course_id = $this->student->course_id;
            $this->status = EnrollmentStatus::Enrolled->value;
            $this->remarks = '';
            $this->class_schedule_ids = [];

            if ($this->section_id) {
                $this->class_schedule_ids = app(StudentEnrollmentService::class)
                    ->defaultClassScheduleIds($this->section_id, $this->academic_year_id);
            }

            return;
        }

        $this->department_id = $enrollment->gradeLevel?->department_id;
        $this->grade_level_id = $enrollment->grade_level_id;
        $this->section_id = $enrollment->section_id;
        $this->course_id = $enrollment->course_id;
        $this->status = $enrollment->status->value;
        $this->enrollment_date = $enrollment->enrollment_date?->toDateString();
        $this->remarks = $enrollment->remarks ?? '';
        $this->class_schedule_ids = $enrollment->classSchedules->pluck('id')->all();
    }

    protected function ensureValidSemesterFilter(): void
    {
        $options = $this->semesterFilterOptions();

        if ($this->semester_filter !== '' && ! array_key_exists($this->semester_filter, $options)) {
            $this->semester_filter = '';
        }
    }

    /**
     * @return array<string, string>
     */
    protected function semesterFilterOptions(): array
    {
        $department = null;

        if ($this->department_id) {
            $department = Department::query()->find($this->department_id);
        } elseif ($this->grade_level_id) {
            $department = GradeLevel::query()->with('department')->find($this->grade_level_id)?->department;
        } elseif ($this->section_id) {
            $department = Section::query()->with('gradeLevel.department')->find($this->section_id)?->gradeLevel?->department;
        }

        return $department?->semesterOptions() ?? Semester::options();
    }

    protected function availableClasses()
    {
        if (! $this->section_id || ! $this->academic_year_id) {
            return collect();
        }

        return app(StudentEnrollmentService::class)->availableClasses(
            $this->section_id,
            $this->academic_year_id,
            $this->semester_filter ?: null,
        );
    }

    protected function viewData(): array
    {
        $gradeLevel = $this->grade_level_id
            ? GradeLevel::query()->with('department')->find($this->grade_level_id)
            : null;

        return [
            'statuses' => EnrollmentStatus::options(),
            'departments' => Department::query()->active()->ordered()->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'gradeLevels' => GradeLevel::query()
                ->when($this->department_id, fn ($q) => $q->where('department_id', $this->department_id))
                ->orderBy('sort_order')
                ->get(),
            'sections' => Section::query()
                ->with(['course', 'gradeLevel'])
                ->when($this->grade_level_id, fn ($q) => $q->where('grade_level_id', $this->grade_level_id))
                ->when($this->academic_year_id, fn ($q) => $q->where(function ($query) {
                    $query->where('academic_year_id', $this->academic_year_id)
                        ->orWhereNull('academic_year_id');
                }))
                ->orderBy('course_id')
                ->orderBy('name')
                ->get(),
            'courses' => Course::query()
                ->when($this->grade_level_id, fn ($q) => $q->where('grade_level_id', $this->grade_level_id))
                ->orderBy('name')
                ->get(),
            'availableClasses' => $this->availableClasses(),
            'semesterOptions' => $this->semesterFilterOptions(),
            'showCourseField' => $gradeLevel?->department?->code === 'shs',
            'selectedSection' => $this->section_id
                ? Section::query()->with('course')->find($this->section_id)
                : null,
        ];
    }

    public function render()
    {
        return view('livewire.students.enroll', $this->viewData())
            ->title('Enroll '.$this->student->full_name);
    }
}
