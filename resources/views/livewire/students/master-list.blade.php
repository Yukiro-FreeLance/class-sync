<div class="student-list-page">
    <x-page-header title="Master List" subtitle="Official list of students by grade level">
        <x-slot name="actions">
            <a href="{{ route('students.index') }}" wire:navigate class="btn-secondary">All Students</a>
            <a href="{{ route('students.lists.class') }}" wire:navigate class="btn-secondary">Class List</a>
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
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Section (optional)</label>
                <select wire:model.live="section" class="select-field" {{ $grade ? '' : 'disabled' }}>
                    <option value="">All sections</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm pb-2.5">
                    <input wire:model.live="activeOnly" type="checkbox" class="rounded text-green-700">
                    Active students only
                </label>
            </div>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Gender</label>
                <select wire:model.live="gender" class="select-field">
                    @foreach ($genderFilters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if (! $grade)
        <div class="panel text-center py-16 no-print">
            <p class="text-slate-500">Select a department and grade to generate the master list.</p>
        </div>
    @elseif ($totalCount === 0)
        <div class="panel text-center py-16">
            <p class="text-slate-500">No students found for the selected filters.</p>
        </div>
    @else
        <div class="panel student-list-document">
            <x-student-list-print-header
                :title="'Master List — '.($gradeLevel?->name ?? 'Grade')"
                :subtitle="$section ? 'Section filter applied' : 'All sections'"
                :school="$school"
                :academic-year="$academicYear"
            />

            <p class="text-sm text-slate-600 mb-4 text-center print-only">Total enrollees: <strong>{{ $totalCount }}</strong></p>
            <p class="text-sm text-slate-600 mb-4 text-center no-print">Showing <strong>{{ $totalCount }}</strong> student(s)</p>

            @foreach ($groupedStudents as $sectionName => $sectionStudents)
                <div @class(['mb-8', 'break-inside-avoid' => true])>
                    @if ($groupedStudents->count() > 1)
                        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 mb-2 border-b border-slate-200 pb-1">
                            Section {{ $sectionName }} ({{ $sectionStudents->count() }})
                        </h3>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full student-list-table text-sm">
                            <thead>
                                <tr>
                                    <th class="w-10">#</th>
                                    <th>Student No.</th>
                                    <th>Name</th>
                                    <th class="w-12">Sex</th>
                                    <th>Birthdate</th>
                                    <th>Section</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $genderGroups = \App\Services\Students\StudentListService::groupByGender($sectionStudents); @endphp
                                @foreach ($genderGroups as $genderKey => $genderStudents)
                                    <x-student-gender-header :colspan="7" :gender-key="$genderKey" :count="$genderStudents->count()" :groups="$genderGroups" />
                                    @foreach ($genderStudents as $index => $student)
                                        <tr>
                                            <td class="text-center text-slate-500">{{ $index + 1 }}</td>
                                            <td class="font-mono text-xs">{{ $student->student_number }}</td>
                                            <td>{{ $student->list_name }}</td>
                                            <td class="text-center">{{ \App\Services\Students\StudentListService::formatGender($student->gender) }}</td>
                                            <td>{{ $student->birth_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $student->section?->name ?? '—' }}</td>
                                            <td class="text-xs max-w-[200px] truncate print:whitespace-normal print:max-w-none">{{ $student->address ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="mt-10 pt-6 border-t border-slate-200 grid sm:grid-cols-2 gap-8 text-sm print-only">
                <div>
                    <p class="text-slate-500 mb-8">Prepared by:</p>
                    <p class="border-t border-slate-400 pt-1 font-medium">_________________________</p>
                    <p class="text-xs text-slate-500 mt-1">Registrar / Class Adviser</p>
                </div>
                <div>
                    <p class="text-slate-500 mb-8">Approved by:</p>
                    <p class="border-t border-slate-400 pt-1 font-medium">_________________________</p>
                    <p class="text-xs text-slate-500 mt-1">School Head / Principal</p>
                </div>
            </div>
        </div>
    @endif
</div>
