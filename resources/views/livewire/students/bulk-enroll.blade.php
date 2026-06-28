<div>
    <x-page-header title="Bulk Enrollment" subtitle="Enroll students in bulk or assign subjects to a section">
        <x-slot name="actions">
            <a href="{{ route('students.index') }}" wire:navigate class="btn-secondary">All Students</a>
        </x-slot>
    </x-page-header>

    <div class="flex flex-wrap gap-2 mb-6">
        <button type="button" wire:click="$set('mode', 'section')"
            @class([
                'px-4 py-2 rounded-xl text-sm font-medium transition',
                'bg-brand-600 text-white shadow-sm' => $mode === 'section',
                'btn-secondary' => $mode !== 'section',
            ])>
            Bulk Section Enrollment
        </button>
        <button type="button" wire:click="$set('mode', 'subjects')"
            @class([
                'px-4 py-2 rounded-xl text-sm font-medium transition',
                'bg-brand-600 text-white shadow-sm' => $mode === 'subjects',
                'btn-secondary' => $mode !== 'subjects',
            ])>
            Assign Subjects
        </button>
    </div>

    <div class="panel mb-5">
        <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Academic Year</label>
                <select wire:model.live="academicYearId" class="select-field">
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Department</label>
                <select wire:model.live="department" class="select-field">
                    <option value="">All departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Grade</label>
                <select wire:model.live="grade" class="select-field" {{ $department ? '' : 'disabled' }}>
                    <option value="">Select grade</option>
                    @foreach ($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Section</label>
                <select wire:model.live="section" class="select-field" {{ $grade ? '' : 'disabled' }}>
                    <option value="">Select section</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Semester filter</label>
                <select wire:model.live="semesterFilter" class="select-field" {{ $section ? '' : 'disabled' }}>
                    <option value="">All semesters</option>
                    @foreach ($semesterOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($mode === 'section')
                <div>
                    <label class="text-[11px] font-medium text-slate-500 mb-1 block">Show students</label>
                    <select wire:model.live="studentScope" class="select-field" {{ $grade ? '' : 'disabled' }}>
                        <option value="grade">All in grade (needs enrollment)</option>
                        <option value="unassigned">No section assigned</option>
                        <option value="section">Currently in this section</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="grid xl:grid-cols-[340px_1fr] gap-5 items-start">
        {{-- Left: Subjects & settings --}}
        <aside class="xl:sticky xl:top-24 space-y-4">
            @if ($section && $selectedSection)
                <div class="panel space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">Target Section</p>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ $selectedSection->gradeLevel?->name }} · {{ $selectedSection->name }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">{{ $selectedSection->gradeLevel?->department?->name }}</p>
                    </div>

                    @if ($mode === 'section')
                        <div class="space-y-3 pt-3 border-t border-surface-border dark:border-slate-800">
                            <div>
                                <label class="text-xs font-medium text-slate-500 mb-1 block">Status</label>
                                <select wire:model="status" class="select-field text-sm">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-slate-500 mb-1 block">Enrollment date</label>
                                <input wire:model="enrollmentDate" type="date" class="input-field text-sm">
                            </div>
                        </div>
                    @else
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 pt-3 border-t border-surface-border dark:border-slate-800">
                            <input type="checkbox" wire:model="mergeExistingSubjects" class="rounded text-brand-600">
                            Keep existing subject assignments
                        </label>
                    @endif

                    <div class="pt-3 border-t border-surface-border dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subjects</p>
                            <div class="flex gap-1">
                                <button type="button" wire:click="selectAllSubjects" class="text-[11px] text-brand-600">All</button>
                                <span class="text-slate-300">·</span>
                                <button type="button" wire:click="clearSubjects" class="text-[11px] text-slate-500">Clear</button>
                            </div>
                        </div>

                        @if ($subjectGroups->isEmpty())
                            <p class="text-xs text-amber-600">No schedules for this section. Add them in Academic → Schedules.</p>
                        @else
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach ($subjectGroups as $group)
                                    <label wire:key="subject-{{ $group->subject_id }}"
                                        class="flex items-start gap-2 rounded-lg border border-surface-border dark:border-slate-800 px-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                        <input type="checkbox" wire:model="selectedSubjectIds" value="{{ $group->subject_id }}"
                                            class="mt-0.5 rounded text-brand-600">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                                {{ $group->subject?->name ?? 'Subject' }}
                                                @if ($group->subject?->code)
                                                    <span class="text-slate-400 font-normal">({{ $group->subject->code }})</span>
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                {{ $group->schedules->count() }} schedule slot(s)
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2">{{ $scheduleCount }} class slot(s) will be assigned</p>
                        @endif
                        <x-input-error :messages="$errors->get('selectedSubjectIds')" class="mt-2" />
                    </div>

                    @if ($mode === 'section')
                        <button type="button" wire:click="enrollBulk" wire:loading.attr="disabled"
                            @disabled(count($selectedStudentIds) === 0 || count($selectedSubjectIds) === 0)
                            class="btn-primary w-full text-sm">
                            <span wire:loading.remove wire:target="enrollBulk">Enroll {{ count($selectedStudentIds) }} Student(s)</span>
                            <span wire:loading wire:target="enrollBulk">Enrolling…</span>
                        </button>
                    @else
                        <button type="button" wire:click="assignSubjects" wire:loading.attr="disabled"
                            @disabled(count($selectedStudentIds) === 0 || count($selectedSubjectIds) === 0)
                            class="btn-primary w-full text-sm">
                            <span wire:loading.remove wire:target="assignSubjects">Assign to {{ count($selectedStudentIds) }} Student(s)</span>
                            <span wire:loading wire:target="assignSubjects">Assigning…</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="panel text-center py-10">
                    <p class="text-sm text-slate-500">Select grade and section to configure enrollment.</p>
                </div>
            @endif
        </aside>

        {{-- Right: Student list --}}
        <div class="panel-flush min-w-0">
            <div class="px-4 sm:px-5 py-3.5 border-b border-surface-border dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Students</h3>
                            <span class="text-xs font-medium text-slate-500 bg-white dark:bg-slate-800 border border-surface-border dark:border-slate-700 rounded-full px-2 py-0.5">
                                {{ count($selectedStudentIds) }} / {{ $students->count() }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            @if ($mode === 'section')
                                Select students to enroll into the target section.
                            @else
                                Select enrolled students to update subject assignments.
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input wire:model.live.debounce.300ms="studentSearch" type="search"
                            placeholder="Search…" class="input-field text-sm w-40">
                        <button type="button" wire:click="selectAllStudents" class="btn-secondary text-xs py-1.5">Select all</button>
                        <button type="button" wire:click="clearStudents" class="btn-ghost text-xs py-1.5">Clear</button>
                    </div>
                </div>
            </div>

            <div class="max-h-[calc(100vh-16rem)] overflow-y-auto divide-y divide-surface-border dark:divide-slate-800">
                @forelse ($students as $student)
                    @php
                        $enrollment = $student->enrollments->firstWhere('academic_year_id', $academicYearId);
                        $isSelected = in_array($student->id, $selectedStudentIds, true);
                    @endphp
                    <label wire:key="student-{{ $student->id }}"
                        @class([
                            'flex items-center gap-3 px-4 sm:px-5 py-3.5 cursor-pointer transition',
                            'bg-brand-50/50 dark:bg-brand-900/10' => $isSelected,
                            'hover:bg-slate-50/70 dark:hover:bg-slate-900/40' => ! $isSelected,
                        ])>
                        <input type="checkbox" wire:model="selectedStudentIds" value="{{ $student->id }}"
                            class="rounded text-brand-600 shrink-0">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600">
                            {{ strtoupper(mb_substr($student->first_name, 0, 1).mb_substr($student->last_name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $student->full_name }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">{{ $student->student_number }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            @if ($student->section)
                                <p class="text-xs text-slate-600 dark:text-slate-300">{{ $student->section->name }}</p>
                            @else
                                <p class="text-xs text-amber-600">No section</p>
                            @endif
                            @if ($enrollment && $enrollment->classSchedules->isNotEmpty())
                                <p class="text-[10px] text-slate-400">{{ $enrollment->classSchedules->count() }} class(es)</p>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="px-5 py-16 text-center text-sm text-slate-500">
                        @if (! $grade)
                            Select a grade to load students.
                        @elseif ($mode === 'subjects' && ! $section)
                            Select a section to load enrolled students.
                        @else
                            No students match the current filters.
                        @endif
                    </div>
                @endforelse
            </div>

            <x-input-error :messages="$errors->get('selectedStudentIds')" class="px-5 py-2" />
        </div>
    </div>
</div>
