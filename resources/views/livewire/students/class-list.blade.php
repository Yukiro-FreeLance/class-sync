<div class="student-list-page">
    <x-page-header title="Class List" subtitle="Section roster for advisers and subject teachers">
        <x-slot name="actions">
            <a href="{{ route('students.index') }}" wire:navigate class="btn-secondary">All Students</a>
            <a href="{{ route('students.lists.master') }}" wire:navigate class="btn-secondary">Master List</a>
            @if ($canExport)
                <div x-data="{ exportOpen: false }" class="relative no-print">
                    <button type="button" @click="exportOpen = !exportOpen" class="btn-secondary">Export</button>
                    <div x-show="exportOpen" @click.outside="exportOpen = false" x-cloak
                        class="absolute right-0 mt-2 w-44 rounded-xl border border-surface-border bg-white dark:bg-slate-900 shadow-lg py-1 z-20">
                        <a href="{{ $this->exportUrl('xlsx') }}"
                            class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">Excel (.xlsx)</a>
                        <a href="{{ $this->exportUrl('csv') }}"
                            class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">CSV (.csv)</a>
                    </div>
                </div>
                <button type="button" onclick="window.print()" class="btn-primary no-print">Print</button>
            @endif
        </x-slot>
    </x-page-header>

    <div class="panel mb-6 no-print">
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
                    <option value="">Select department</option>
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
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Subject (optional)</label>
                <select wire:model.live="subject" class="select-field" {{ $section ? '' : 'disabled' }}>
                    <option value="">Whole section</option>
                    @foreach ($subjects as $subj)
                        <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm pb-2.5">
                    <input wire:model.live="activeOnly" type="checkbox" class="rounded text-green-700">
                    Active only
                </label>
            </div>
        </div>
    </div>

    @if (! $section)
        <div class="panel text-center py-16 no-print">
            <p class="text-slate-500">Select department, grade, and section to view the class list.</p>
        </div>
    @elseif ($students->isEmpty())
        <div class="panel text-center py-16">
            <p class="text-slate-500">No students found in this section{{ $selectedSubject ? ' for '.$selectedSubject->name : '' }}.</p>
        </div>
    @else
        <div class="panel student-list-document">
            @php
                $gradeName = $sectionContext?->gradeLevel?->name ?? '';
                $sectionName = $sectionContext?->name ?? '';
                $listTitle = $selectedSubject
                    ? "Class List — {$selectedSubject->name}"
                    : "Class List — {$gradeName} {$sectionName}";
            @endphp

            <x-student-list-print-header
                :title="$listTitle"
                :subtitle="$selectedSubject ? $gradeName.' '.$sectionName : null"
                :school="$school"
                :academic-year="$academicYear"
            />

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6 text-sm">
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Grade & Section</p>
                    <p class="font-semibold">{{ $gradeName }} — {{ $sectionName }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Class Adviser</p>
                    <p class="font-semibold">{{ $sectionContext?->adviser?->name ?? '—' }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Room</p>
                    <p class="font-semibold">{{ $sectionContext?->assignedRoom?->name ?? $sectionContext?->room ?? '—' }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Total Students</p>
                    <p class="font-semibold">{{ $students->count() }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full student-list-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th class="w-12">Sex</th>
                            <th class="w-28">Birthdate</th>
                            <th class="no-print">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $index => $student)
                            <tr>
                                <td class="text-center text-slate-500">{{ $index + 1 }}</td>
                                <td class="font-mono text-xs">{{ $student->student_number }}</td>
                                <td class="font-medium">{{ \App\Services\Students\StudentListService::formatName($student, 'formal') }}</td>
                                <td class="text-center">{{ \App\Services\Students\StudentListService::formatGender($student->gender) }}</td>
                                <td>{{ $student->birth_date?->format('m/d/Y') ?? '—' }}</td>
                                <td class="no-print">
                                    <span class="badge bg-emerald-100 text-emerald-700">{{ $student->status?->label() ?? '—' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-200 text-sm print-only">
                <div class="grid sm:grid-cols-2 gap-8">
                    <div>
                        <p class="text-slate-500 mb-8">Subject Teacher / Adviser:</p>
                        <p class="border-t border-slate-400 pt-1 font-medium">_________________________</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-8">Date:</p>
                        <p class="border-t border-slate-400 pt-1 font-medium">{{ now()->format('F d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
