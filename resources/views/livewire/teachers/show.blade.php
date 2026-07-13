<div>
    <div class="mb-6">
        <a href="{{ route('teachers.index') }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr;
            Back to Teachers</a>
        <div class="flex flex-wrap items-center gap-4 mt-4">
            <div
                class="h-14 w-14 rounded-2xl bg-green-700 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-brand-500/30 shrink-0">
                {{ strtoupper(substr($teacher->first_name ?: $teacher->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="page-title">{{ $teacher->full_name }}</h1>
                <p class="page-subtitle">
                    {{ $teacher->email }}
                    @if ($teacher->username)
                        &middot; <span class="font-mono">{{ $teacher->username }}</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <span @class([
                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                    'bg-emerald-100 text-emerald-700' => $teacher->is_active,
                    'bg-slate-100 text-slate-600' => ! $teacher->is_active,
                ])>{{ $teacher->is_active ? 'Active' : 'Inactive' }}</span>
                <span class="inline-flex items-center rounded-full bg-brand-100 text-green-800 px-2.5 py-1 text-xs font-medium">
                    {{ $teacher->advised_sections_count }} adviser section(s)
                </span>
                <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-medium">
                    {{ $teacher->class_schedules_count }} class schedule(s)
                </span>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-12 gap-6 mb-6">
        <div class="lg:col-span-4 panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Advisory Sections</h3>
            <div class="space-y-2">
                @forelse ($advisedSections as $advised)
                    <div class="rounded-xl border border-surface-border dark:border-slate-800 px-3 py-2">
                        <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $advised->name }}</p>
                        <p class="text-xs text-slate-500">{{ $advised->gradeLevel?->name ?? '—' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No advisory sections assigned.</p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-8 panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Class Schedules</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @forelse ($classSchedules as $schedule)
                    <div class="flex items-start justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-800 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate">
                                {{ $schedule->subject?->name ?? 'Class' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $schedule->section?->gradeLevel?->name }} {{ $schedule->section?->name }}
                                @if ($schedule->room)
                                    &middot; {{ $schedule->room->name }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xs text-slate-500 shrink-0">
                            {{ substr((string) $schedule->starts_at, 0, 5) }} – {{ substr((string) $schedule->ends_at, 0, 5) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No class schedules assigned.</p>
                @endforelse
            </div>
        </div>
    </div>

    <x-page-header title="All Students" :subtitle="$students->total() . ' student(s) in assigned sections'" />

    <div class="panel mb-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Search by name, ID, or RFID..." class="input-field">
            </div>
            <select wire:model.live="section" class="select-field">
                <option value="">All Sections</option>
                @foreach ($sections as $sectionOption)
                    <option value="{{ $sectionOption->id }}">
                        {{ $sectionOption->gradeLevel?->name }} - {{ $sectionOption->name }}
                    </option>
                @endforeach
            </select>
            <select wire:model.live="status" class="select-field">
                <option value="">All Statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="panel-flush">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Name</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $genderGroups = \App\Services\Students\StudentListService::groupByGender(collect($students->items())); @endphp
                    @forelse ($genderGroups as $genderKey => $genderStudents)
                        <x-student-gender-header :colspan="6" :gender-key="$genderKey" :count="$genderStudents->count()" :groups="$genderGroups" />
                        @foreach ($genderStudents as $student)
                        <tr>
                            <td class="font-mono text-xs">{{ $student->student_number }}</td>
                            <td class="font-medium">{{ $student->list_name }}</td>
                            <td>{{ $student->gradeLevel?->name ?? '—' }}</td>
                            <td>{{ $student->section?->name ?? '—' }}</td>
                            <td>
                                @php $studentStatus = $student->status; @endphp
                                <span @class([
                                    'badge',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' =>
                                        $studentStatus?->value === 'active',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' =>
                                        $studentStatus?->value !== 'active',
                                ])>{{ $studentStatus?->label() ?? '—' }}</span>
                            </td>
                            <td class="text-right">
                                @can('view', $student)
                                    <a href="{{ route('students.show', $student) }}" wire:navigate
                                        class="text-green-700 hover:text-brand-500 font-medium text-sm">View</a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <p class="text-slate-500">No students found for this teacher's assigned sections.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="px-5 py-4 border-t border-surface-border dark:border-slate-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
