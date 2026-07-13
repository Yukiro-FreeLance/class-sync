<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Services\Students\StudentListService;
use App\Services\Students\StudentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Students')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $grade = '';

    #[Url]
    public string $department = '';

    #[Url]
    public string $section = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $showArchived = false;

    #[Url]
    public string $sort = 'name';

    #[Url]
    public string $direction = 'asc';

    #[Url]
    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartment(): void
    {
        $this->reset(['grade', 'section']);
        $this->resetPage();
    }

    public function updatingGrade(): void
    {
        $this->resetPage();
    }

    public function updatingSection(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowed = ['name', 'student_number', 'grade', 'status'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'grade', 'department', 'section', 'status', 'showArchived', 'sort', 'direction']);
        $this->perPage = 15;
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Student::class);
    }

    public function openImport(): void
    {
        $this->authorize('create', Student::class);
        $this->dispatch('open-student-import');
    }

    #[On('students-imported')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function exportUrl(string $format = 'xlsx'): string
    {
        return route('students.export', array_filter([
            'format' => $format,
            'search' => $this->search ?: null,
            'grade' => $this->grade ?: null,
            'section' => $this->section ?: null,
            'status' => $this->status ?: null,
        ]));
    }

    public function archive(int $studentId, StudentService $studentService): void
    {
        $student = Student::query()->findOrFail($studentId);
        $this->authorize('archive', $student);

        $studentService->archive($student);

        $this->dispatch('toast', message: 'Student archived successfully.', type: 'success');
        $this->resetPage();
    }

    public function restore(int $studentId, StudentService $studentService): void
    {
        $student = Student::onlyTrashed()->findOrFail($studentId);
        $this->authorize('restore', $student);

        $studentService->restore($student);

        $this->dispatch('toast', message: 'Student restored successfully.', type: 'success');
        $this->resetPage();
    }

    public function forceDelete(int $studentId, StudentService $studentService): void
    {
        $student = Student::withTrashed()->findOrFail($studentId);
        $this->authorize('delete', $student);

        $studentService->forceDelete($student);

        $this->dispatch('toast', message: 'Student permanently deleted.', type: 'success');
        $this->resetPage();
    }

    public function setRecordFilter(string $filter): void
    {
        if (! in_array($filter, ['active', 'archived'], true)) {
            return;
        }

        $this->showArchived = $filter === 'archived';
        $this->resetPage();
    }

    protected function viewData(): array
    {
        $baseQuery = Student::query()
            ->when($this->showArchived, fn ($q) => $q->onlyTrashed());

        $students = (clone $baseQuery)
            ->with(['gradeLevel.department', 'section'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('student_number', 'like', "%{$this->search}%")
                        ->orWhere('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('rfid_tag', 'like', "%{$this->search}%");
                });
            })
            ->when($this->department, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
            ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
            ->when($this->section, fn ($q) => $q->where('section_id', $this->section))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->tap(fn ($query) => $this->applyStudentSort($query))
            ->paginate($this->normalizedPerPage());

        return [
            'students' => $students,
            'studentGenderGroups' => $this->sort === 'name'
                ? StudentListService::groupByGender($students->getCollection())
                : collect(['all' => $students->getCollection()]),
            'groupStudentsByGender' => $this->sort === 'name',
            'sort' => $this->sort,
            'direction' => $this->direction,
            'perPage' => $this->normalizedPerPage(),
            'perPageOptions' => [10, 15, 25, 50],
            'stats' => [
                'active' => Student::query()->count(),
                'archived' => Student::onlyTrashed()->count(),
            ],
            'canManageLifecycle' => auth()->user()->can('students.archive')
                || auth()->user()->can('students.restore')
                || auth()->user()->can('students.delete'),
            'departments' => Department::query()->active()->ordered()->get(),
            'grades' => GradeLevel::query()
                ->when($this->department, fn ($q) => $q->where('department_id', $this->department))
                ->orderBy('sort_order')
                ->get(),
            'sections' => Section::query()
                ->when($this->grade, fn ($q) => $q->where('grade_level_id', $this->grade))
                ->when($this->department, fn ($q) => $q->whereHas('gradeLevel', fn ($g) => $g->where('department_id', $this->department)))
                ->orderBy('name')
                ->get(),
            'statuses' => StudentStatus::options(),
        ];
    }

    public function render()
    {
        return view('livewire.students.index', $this->viewData());
    }

    protected function normalizedPerPage(): int
    {
        return in_array($this->perPage, [10, 15, 25, 50], true) ? $this->perPage : 15;
    }

    protected function applyStudentSort($query): void
    {
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        match ($this->sort) {
            'student_number' => $query->orderBy('student_number', $direction),
            'grade' => $query
                ->orderBy(
                    GradeLevel::select('sort_order')
                        ->whereColumn('grade_levels.id', 'students.grade_level_id')
                        ->limit(1),
                    $direction,
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('middle_name'),
            'status' => $query->orderBy('status', $direction)->orderBy('last_name')->orderBy('first_name')->orderBy('middle_name'),
            default => $query
                ->orderByRaw(StudentListService::genderOrderExpression().' ASC')
                ->orderBy('last_name', $direction)
                ->orderBy('first_name', $direction)
                ->orderBy('middle_name', $direction),
        };
    }
}
