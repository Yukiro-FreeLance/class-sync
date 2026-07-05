<div>
    <div class="mb-8">
        <a href="{{ route('students.show', $student) }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr; Back to {{ $student->full_name }}</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">Edit Student</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $student->student_number }}</p>
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
                <x-text-input wire:model="rfid_tag" id="rfid_tag" class="mt-1 block w-full font-mono" placeholder="Optional" />
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

        <p class="mt-6 text-sm text-gray-500">
            To update grade level, section, subjects, or classes, use
            <a href="{{ route('students.enroll', $student) }}" wire:navigate class="text-green-700 hover:text-brand-500 font-medium">Enroll</a>.
        </p>

        <div class="mt-8 flex gap-3">
            <x-primary-button type="submit">Save Changes</x-primary-button>
            <a href="{{ route('students.show', $student) }}" wire:navigate class="inline-flex items-center px-4 py-2 glass rounded-md text-sm">Cancel</a>
        </div>
    </form>
</div>
