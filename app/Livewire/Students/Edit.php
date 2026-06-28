<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Services\Students\StudentService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public Student $student;

    public string $student_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public ?string $middle_name = null;

    public ?string $gender = null;

    public ?string $birth_date = null;

    public ?string $address = null;

    public string $status = 'active';

    public ?string $rfid_tag = null;

    public ?string $medical_notes = null;

    public function mount(Student $student): void
    {
        if ($student->trashed()) {
            abort(404);
        }

        $this->authorize('update', $student);

        $this->student = $student;
        $this->student_number = $student->student_number;
        $this->first_name = $student->first_name;
        $this->last_name = $student->last_name;
        $this->middle_name = $student->middle_name;
        $this->gender = $student->gender;
        $this->birth_date = $student->birth_date?->toDateString();
        $this->address = $student->address;
        $this->status = $student->status?->value ?? StudentStatus::Active->value;
        $this->rfid_tag = $student->rfid_tag;
        $this->medical_notes = $student->medical_notes;
    }

    public function rules(): array
    {
        return [
            'student_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_number')->ignore($this->student->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(StudentStatus::options()))],
            'rfid_tag' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('students', 'rfid_tag')->ignore($this->student->id),
            ],
            'medical_notes' => ['nullable', 'string'],
        ];
    }

    public function save(StudentService $studentService): void
    {
        $this->authorize('update', $this->student);

        $this->rfid_tag = blank($this->rfid_tag) ? null : trim($this->rfid_tag);
        $this->middle_name = blank($this->middle_name) ? null : trim($this->middle_name);
        $this->gender = blank($this->gender) ? null : trim($this->gender);
        $this->address = blank($this->address) ? null : trim($this->address);
        $this->medical_notes = blank($this->medical_notes) ? null : trim($this->medical_notes);

        $data = $this->validate();
        $numberChanged = $data['student_number'] !== $this->student->student_number;

        $student = $studentService->update($this->student, $data);

        if ($numberChanged) {
            $studentService->generateQrCode($student);
        }

        $this->dispatch('toast', message: 'Student updated successfully.', type: 'success');
        $this->redirect(route('students.show', $student), navigate: true);
    }

    public function render()
    {
        return view('livewire.students.edit', [
            'statuses' => StudentStatus::options(),
        ])->title('Edit '.$this->student->full_name);
    }
}
