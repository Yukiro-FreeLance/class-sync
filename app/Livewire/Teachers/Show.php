<?php

namespace App\Livewire\Teachers;

use App\Enums\StudentStatus;
use App\Models\Section;
use App\Models\User;
use App\Services\Academic\TeacherScopeService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Teacher Students')]
class Show extends Component
{
    use WithPagination;

    public User $teacher;

    #[Url]
    public string $search = '';

    #[Url]
    public string $section = '';

    #[Url]
    public string $status = '';

    public function mount(User $teacher): void
    {
        abort_unless(
            User::query()->assignableAsTeacher()->whereKey($teacher->id)->exists(),
            404
        );

        $this->authorize('view', $teacher);

        $this->teacher = $teacher->loadCount(['advisedSections', 'classSchedules']);
    }

    public function updatingSearch(): void
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

    public function title(): string
    {
        return $this->teacher->full_name;
    }

    public function render(TeacherScopeService $teacherScope)
    {
        $sectionIds = $teacherScope->accessibleSectionIds($this->teacher);

        $students = $teacherScope->studentsQuery($this->teacher)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('student_number', 'like', "%{$this->search}%")
                        ->orWhere('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('rfid_tag', 'like', "%{$this->search}%");
                });
            })
            ->when($this->section, fn ($q) => $q->where('section_id', $this->section))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->paginate(20);

        return view('livewire.teachers.show', [
            'students' => $students,
            'sections' => Section::query()
                ->with('gradeLevel')
                ->whereIn('id', $sectionIds ?: [-1])
                ->orderBy('name')
                ->get(),
            'statuses' => StudentStatus::options(),
            'advisedSections' => $this->teacher->advisedSections()
                ->with('gradeLevel')
                ->orderBy('name')
                ->get(),
            'classSchedules' => $this->teacher->classSchedules()
                ->with(['subject', 'section.gradeLevel', 'room'])
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
        ]);
    }
}
