<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Services\Students\StudentService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Add Student')]
class Create extends Component
{
    public string $student_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public ?string $middle_name = null;

    public ?string $gender = null;

    public ?string $birth_date = null;

    public ?string $address = null;

    public ?int $grade_level_id = null;

    public ?int $department_id = null;

    public ?int $section_id = null;

    public ?int $academic_year_id = null;

    public string $status = 'active';

    public ?string $rfid_tag = null;

    public ?string $medical_notes = null;

    public function mount(): void
    {
        $this->student_number = 'STU-'.strtoupper(Str::random(8));
        $this->academic_year_id = AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
    }

    public function updatedDepartmentId(): void
    {
        $this->grade_level_id = null;
        $this->section_id = null;
    }

    public function updatedGradeLevelId(): void
    {
        $this->section_id = null;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(StudentStatus::options()))],
            'rfid_tag' => ['nullable', 'string', 'max:100', 'unique:students,rfid_tag'],
            'medical_notes' => ['nullable', 'string'],
        ];
    }

    public function save(StudentService $studentService): void
    {
        $this->rfid_tag = blank($this->rfid_tag) ? null : trim($this->rfid_tag);
        $this->middle_name = blank($this->middle_name) ? null : trim($this->middle_name);
        $this->gender = blank($this->gender) ? null : trim($this->gender);
        $this->address = blank($this->address) ? null : trim($this->address);
        $this->medical_notes = blank($this->medical_notes) ? null : trim($this->medical_notes);

        $data = $this->validate();

        $student = $studentService->create($data);

        $this->dispatch('toast', message: 'Student created successfully.', type: 'success');

        $this->redirect(route('students.show', $student), navigate: true);
    }

    protected function viewData(): array
    {
        return [
            'statuses' => StudentStatus::options(),
            'departments' => Department::query()->active()->ordered()->get(),
            'gradeLevels' => GradeLevel::query()
                ->with('department')
                ->when($this->department_id, fn ($q) => $q->where('department_id', $this->department_id))
                ->orderBy('sort_order')
                ->get(),
            'sections' => Section::query()
                ->when($this->grade_level_id, fn ($q) => $q->where('grade_level_id', $this->grade_level_id))
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ];
    }

    public function render()
    {
        return view('livewire.students.create', $this->viewData());
    }
}
