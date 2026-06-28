<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['nullable', 'string', 'max:50', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'rfid_tag' => ['nullable', 'string', 'max:100', 'unique:students,rfid_tag'],
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
