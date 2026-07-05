<div>
    <div class="mb-8">
        <a href="{{ route('students.show', $student) }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr; Back to {{ $student->full_name }}</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">Enroll Student</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $student->full_name }} &middot; {{ $student->student_number }}</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="panel max-w-4xl">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Academic Placement</h2>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="academic_year_id" value="Academic Year" />
                    <select wire:model.live="academic_year_id" id="academic_year_id" class="mt-1 input-field">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}@if($year->is_current) (Current)@endif</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('academic_year_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="enrollment_date" value="Enrollment Date" />
                    <x-text-input wire:model="enrollment_date" id="enrollment_date" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('enrollment_date')" class="mt-1" />
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
                    <select wire:model.live="grade_level_id" id="grade_level_id" class="mt-1 input-field" @disabled(!$department_id)>
                        <option value="">Select grade level</option>
                        @foreach ($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('grade_level_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="section_id" value="Section" />
                    <select wire:model.live="section_id" id="section_id" class="mt-1 input-field" @disabled(!$grade_level_id)>
                        <option value="">Select section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->display_label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('section_id')" class="mt-1" />
                </div>

                @if ($showCourseField)
                    <div>
                        <x-input-label for="course_id" value="Track / Strand" />
                        @if ($selectedSection?->course_id)
                            <p class="mt-1 input-field bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200">
                                {{ $selectedSection->course->code }} — {{ $selectedSection->course->name }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">Inherited from the selected section.</p>
                        @else
                            <select wire:model="course_id" id="course_id" class="mt-1 input-field" @disabled(!$grade_level_id)>
                                <option value="">Select strand…</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
                    </div>
                @endif

                <div>
                    <x-input-label for="status" value="Enrollment Status" />
                    <select wire:model="status" id="status" class="mt-1 input-field">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="remarks" value="Remarks" />
                    <textarea wire:model="remarks" id="remarks" rows="2" class="mt-1 input-field" placeholder="Optional notes about this enrollment"></textarea>
                    <x-input-error :messages="$errors->get('remarks')" class="mt-1" />
                </div>
            </div>
        </div>

        <div class="panel max-w-4xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Subjects &amp; Classes</h2>
                    <p class="text-sm text-gray-500 mt-1">Assign class schedules for the selected section.</p>
                </div>
                @if ($section_id)
                    <div class="flex gap-2">
                        <select wire:model.live="semester_filter" class="input-field text-sm">
                            <option value="">All semesters</option>
                            @foreach ($semesterOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="selectAllClasses" class="btn-secondary text-sm">Select all</button>
                        <button type="button" wire:click="clearClasses" class="btn-secondary text-sm">Clear</button>
                    </div>
                @endif
            </div>

            @if (! $section_id)
                <p class="text-sm text-gray-500">Select a section to view available classes.</p>
            @elseif ($availableClasses->isEmpty())
                <p class="text-sm text-gray-500">No class schedules found for this section. Add schedules in Settings &rarr; Academic &rarr; Schedules.</p>
            @else
                <div class="space-y-4">
                    @foreach ($availableClasses->groupBy(fn ($class) => $class->semester->value) as $semester => $classes)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                                {{ $semesterOptions[$semester] ?? \App\Enums\Semester::from($semester)->label() }}
                            </p>
                            <div class="space-y-2">
                                @foreach ($classes as $class)
                                    <label class="flex items-start gap-3 rounded-xl border border-gray-100 dark:border-gray-700/50 p-4 cursor-pointer hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                                        <input
                                            type="checkbox"
                                            wire:model="class_schedule_ids"
                                            value="{{ $class->id }}"
                                            class="mt-1 rounded border-gray-300 text-green-700 focus:ring-brand-500"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ $class->subject?->name ?? 'Subject' }}
                                                @if ($class->subject?->code)
                                                    <span class="text-gray-400 font-normal">({{ $class->subject->code }})</span>
                                                @endif
                                            </p>
                                            <p class="text-sm text-gray-500 mt-1">
                                                {{ $class->day_of_week?->label() }}
                                                {{ substr((string) $class->starts_at, 0, 5) }}–{{ substr((string) $class->ends_at, 0, 5) }}
                                                @if ($class->teacher)
                                                    &middot; {{ $class->teacher->name }}
                                                @endif
                                                @if ($class->room)
                                                    &middot; {{ $class->room->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('class_schedule_ids')" class="mt-2" />
            @endif
        </div>

        <div class="flex gap-3 max-w-4xl">
            <x-primary-button type="submit">Save Enrollment</x-primary-button>
            <a href="{{ route('students.show', $student) }}" wire:navigate class="inline-flex items-center px-4 py-2 glass rounded-md text-sm">Cancel</a>
        </div>
    </form>
</div>
