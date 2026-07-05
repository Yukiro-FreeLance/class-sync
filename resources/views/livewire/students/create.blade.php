<div>
    <div class="mb-8">
        <a href="{{ route('students.index') }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr;
            Back to Students</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">Add Student</h1>
    </div>

    <form wire:submit="save" class="panel max-w-3xl">
        <div class="grid sm:grid-cols-2 gap-6">
            <div class="sm:col-span-2">
                <x-input-label for="student_number" value="LRN No." />
                <x-text-input wire:model="student_number" id="student_number" class="mt-1 block w-full font-mono" />
                <x-input-error :messages="$errors->get('student_number')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="first_name" value="First Name" />
                <x-text-input wire:model="first_name" id="first_name" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="last_name" value="Last Name" />
                <x-text-input wire:model="last_name" id="last_name" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="middle_name" value="Middle Name" />
                <x-text-input wire:model="middle_name" id="middle_name" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('middle_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="gender" value="Gender" />
                <select wire:model="gender" id="gender" class="mt-1 input-field">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <x-input-error :messages="$errors->get('gender')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="birth_date" value="Birth Date" />
                <x-text-input wire:model="birth_date" id="birth_date" type="date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="department_id" value="Department" />
                <select wire:model.live="department_id" id="department_id" class="mt-1 input-field">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="grade_level_id" value="Grade Level" />
                <select wire:model.live="grade_level_id" id="grade_level_id" class="mt-1 input-field"
                    @disabled(!$department_id)>
                    <option value="">Select grade</option>
                    @foreach ($gradeLevels as $gradeLevel)
                        <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('grade_level_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="section_id" value="Section" />
                <select wire:model="section_id" id="section_id" class="mt-1 input-field">
                    <option value="">Select section</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('section_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="academic_year_id" value="Academic Year" />
                <select wire:model="academic_year_id" id="academic_year_id" class="mt-1 input-field">
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="status" value="Status" />
                <select wire:model="status" id="status" class="mt-1 input-field">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="rfid_tag" value="RFID Tag" />
                <x-text-input wire:model="rfid_tag" id="rfid_tag" class="mt-1 block w-full font-mono"
                    placeholder="Optional" />
                <x-input-error :messages="$errors->get('rfid_tag')" class="mt-1" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="address" value="Address" />
                <textarea wire:model="address" id="address" rows="2" class="mt-1 input-field"></textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-1" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="medical_notes" value="Medical Notes" />
                <textarea wire:model="medical_notes" id="medical_notes" rows="2" class="mt-1 input-field"></textarea>
                <x-input-error :messages="$errors->get('medical_notes')" class="mt-1" />
            </div>
        </div>

        <div class="mt-8 flex gap-3">
            <x-primary-button type="submit">Create Student</x-primary-button>
            <a href="{{ route('students.index') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 glass rounded-md text-sm">Cancel</a>
        </div>
    </form>
</div>
