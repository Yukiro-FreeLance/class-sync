<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_number' => ['sometimes', 'string', 'max:50', Rule::unique('students')->ignore($studentId)],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'grade_level_id' => ['sometimes', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'academic_year_id' => ['sometimes', 'exists:academic_years,id'],
            'rfid_tag' => ['nullable', 'string', 'max:100', Rule::unique('students')->ignore($studentId)],
            'status' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'enrollment_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rfid_tag') && blank($this->input('rfid_tag'))) {
            $this->merge(['rfid_tag' => null]);
        }
    }
}
