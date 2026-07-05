<div>
    <x-page-header title="Bulk Class Attendance" subtitle="Mark attendance by subject schedule for an entire section">
        <x-slot name="actions">
            <a href="{{ route('attendance.index') }}" wire:navigate class="btn-secondary">Manual Attendance</a>
            @can('update', \App\Models\Setting::class)
                <a href="{{ route('settings.attendance') }}" wire:navigate class="btn-secondary">Configure Remarks</a>
            @endcan
        </x-slot>
    </x-page-header>

    @if (($isTeacherScoped ?? false) && $sections->isEmpty())
        <div class="panel mb-6 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                No class schedules are assigned to you. Ask the registrar to assign you as the teacher on the class
                schedule, or open <a href="{{ route('settings.academic.schedules') }}" wire:navigate class="font-medium underline">Class Schedules</a>
                to verify your assignments.
            </p>
        </div>
    @elseif (($isTeacherScoped ?? false) && $section && $classSchedules->isEmpty())
        <div class="panel mb-6 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                No classes are scheduled for {{ $weekdayLabel }} on this date in the selected section. Pick another date
                or check the schedule in Academic Settings.
            </p>
        </div>
    @endif

    <div class="panel mb-5">
        <x-attendance-class-filters :departments="$departments" :grades="$grades" :sections="$sections" :class-schedules="$classSchedules"
            :selected-schedule="$selectedSchedule" :weekday-label="$weekdayLabel" :strands="$strands"
            :show-strand-filter="$showStrandFilter" :department-id="$department" :grade-id="$grade"
            :strand-id="$strand" :section-id="$section" :labeled="true"
            :compact-hint="!($section && $classScheduleId)" />
    </div>

    <div class="grid xl:grid-cols-[340px_1fr] gap-5 items-start">
        {{-- Left: Session panel --}}
        <aside class="xl:sticky xl:top-24">
            @if ($section && $classScheduleId && $selectedSchedule)
                <div class="attendance-session-card">
                    <div class="attendance-session-header">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-100/90">Class Session
                        </p>
                        <h2 class="text-xl font-bold mt-0.5 leading-tight">
                            {{ $selectedSchedule->subject?->name ?? 'Class' }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-brand-100">
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 opacity-80" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $selectedSchedule->time_range }}
                            </span>
                            @if ($selectedSection)
                                <span class="opacity-60">·</span>
                                <span>{{ $selectedSection->display_label }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-4">
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 text-slate-600 dark:text-slate-300">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y') }} · {{ $weekdayLabel }}
                            </span>
                            @if ($selectedSchedule->teacher)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 text-slate-600 dark:text-slate-300">
                                    {{ $selectedSchedule->teacher->name }}
                                </span>
                            @endif
                            @if ($selectedSchedule->room)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 text-slate-600 dark:text-slate-300">
                                    {{ $selectedSchedule->room->name }}
                                </span>
                            @endif
                        </div>

                        @if ($totalStudents > 0)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-slate-500">Attendance rate</span>
                                    <span
                                        class="font-semibold text-slate-700 dark:text-slate-200">{{ $attendanceStats['rate'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden flex">
                                    @php $presentPct = $totalStudents > 0 ? ($attendanceStats['present'] / $totalStudents) * 100 : 0; @endphp
                                    <div class="h-full bg-emerald-500 transition-all duration-300"
                                        style="width: {{ $presentPct }}%"></div>
                                    @php $absentPct = $totalStudents > 0 ? ($attendanceStats['absent'] / $totalStudents) * 100 : 0; @endphp
                                    <div class="h-full bg-red-400 transition-all duration-300"
                                        style="width: {{ $absentPct }}%"></div>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">{{ $attendanceStats['present'] }} in class ·
                                    {{ $attendanceStats['absent'] }} out · {{ $totalStudents }} total</p>
                            </div>

                            @if ($remarks->isNotEmpty())
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-2">Tap
                                        to filter list</p>
                                    <div class="grid grid-cols-3 gap-1.5">
                                        <button type="button" wire:click="$set('statusFilter', '')"
                                            @class([
                                                'attendance-stat-pill',
                                                'attendance-stat-pill-active' => $statusFilter === '',
                                            ])>
                                            <span
                                                class="text-base font-bold text-slate-700 dark:text-slate-200">{{ $totalStudents }}</span>
                                            <span class="text-[10px] text-slate-500 mt-0.5">All</span>
                                        </button>
                                        @foreach ($attendanceSummary as $item)
                                            <button type="button"
                                                wire:click="$set('statusFilter', '{{ $item['remark']->id }}')"
                                                @class([
                                                    'attendance-stat-pill',
                                                    'attendance-stat-pill-active' =>
                                                        $statusFilter === (string) $item['remark']->id,
                                                ])>
                                                <span class="text-base font-bold"
                                                    style="color: {{ $item['remark']->color }}">{{ $item['count'] }}</span>
                                                <span
                                                    class="text-[10px] text-slate-500 mt-0.5 truncate w-full text-center">{{ $item['remark']->label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                        Mark all as</p>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        @foreach ($remarks as $remark)
                                            <button type="button" wire:click="markAll({{ $remark->id }})"
                                                class="rounded-xl px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90 active:scale-[0.98]"
                                                style="background-color: {{ $remark->color }}">{{ $remark->label }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <form wire:submit="save" class="pt-1">
                                    <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full">
                                        <span wire:loading.remove wire:target="save">Save Attendance ·
                                            {{ $totalStudents }} students</span>
                                        <span wire:loading wire:target="save">Saving…</span>
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @elseif ($section && !$classScheduleId)
                <div class="panel text-center py-10">
                    <div
                        class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/30 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Select a class</p>
                    <p class="text-xs text-slate-500 mt-1">Choose a subject for {{ $weekdayLabel }}.</p>
                </div>
            @elseif ($section && $classSchedules->isEmpty())
                <div class="panel text-center py-10">
                    <p class="text-sm text-slate-500">No classes on {{ $weekdayLabel }}.</p>
                </div>
            @elseif ($section)
                <div class="panel text-center py-10">
                    <p class="text-sm text-slate-500">No students enrolled.</p>
                </div>
            @else
                <div class="panel text-center py-10">
                    <div
                        class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-100 dark:bg-brand-900/30 text-green-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Ready to take attendance</p>
                    <p class="text-xs text-slate-500 mt-2">Pick grade and section above.</p>
                </div>
            @endif
        </aside>

        {{-- Right: Student list --}}
        <div class="min-w-0">
            @if ($section && $classScheduleId && $totalStudents > 0)
                <div class="panel-flush">
                    <div
                        class="px-4 sm:px-5 py-3.5 border-b border-surface-border dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">Students</h3>
                                    <span
                                        class="text-xs font-medium text-slate-500 bg-white dark:bg-slate-800 border border-surface-border dark:border-slate-700 rounded-full px-2 py-0.5">
                                        {{ $filteredCount }}{{ $studentSearch || $statusFilter ? ' / ' . $totalStudents : '' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Click a status button to mark · expand row
                                    for notes</p>
                            </div>
                            <div class="relative sm:w-56 shrink-0">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input wire:model.live.debounce.300ms="studentSearch" type="search"
                                    placeholder="Search name or ID…" class="input-field text-sm pl-9 py-2">
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-[calc(100vh-13rem)]">
                        <table class="w-full data-table text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>Student</th>
                                    <th class="min-w-[280px]">Status</th>
                                    <th class="w-24">Notes</th>
                                </tr>
                            </thead>
                            @forelse ($students as $index => $student)
                                    @php $entry = $entries[$student->id] ?? null; @endphp
                                    @if ($entry)
                                        @php
                                            $activeRemark = $remarks->firstWhere('id', $entry['remark_id']);
                                            $initials = collect(explode(' ', $student->full_name))
                                                ->take(2)
                                                ->map(fn ($w) => mb_substr($w, 0, 1))
                                                ->join('');
                                            $hasNotes = ($entry['remarks'] ?? '') !== '' || ($entry['went_out'] ?? false);
                                        @endphp
                                        <tbody wire:key="student-{{ $student->id }}" x-data="{ open: @js($hasNotes) }">
                                            <tr class="align-middle">
                                            <td class="text-center text-slate-500 font-medium">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold text-white shadow-sm"
                                                        style="background-color: {{ $activeRemark?->color ?? '#64748b' }}">
                                                        {{ strtoupper($initials) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-medium text-slate-900 dark:text-white truncate">
                                                            {{ $student->full_name }}
                                                        </p>
                                                        <p class="font-mono text-[11px] text-slate-500">
                                                            {{ $student->student_number }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="grid grid-cols-5 gap-1 min-w-[240px]">
                                                    @foreach ($remarks as $remark)
                                                        <button type="button"
                                                            wire:click="setStudentRemark({{ $student->id }}, {{ $remark->id }})"
                                                            title="{{ $remark->label }}" @class([
                                                                'attendance-status-btn',
                                                                'attendance-status-btn-active' => (int) $entry['remark_id'] === $remark->id,
                                                                'attendance-status-btn-inactive' => (int) $entry['remark_id'] !== $remark->id,
                                                            ])
                                                            @if ((int) $entry['remark_id'] === $remark->id) style="background-color: {{ $remark->color }}" @endif>
                                                            <span class="hidden sm:inline truncate">{{ $remark->label }}</span>
                                                            <span class="sm:hidden">{{ mb_substr($remark->label, 0, 1) }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" @click="open = !open" @class([
                                                    'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition',
                                                    'text-green-700 bg-brand-50 dark:bg-brand-900/20' => $hasNotes,
                                                    'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' => ! $hasNotes,
                                                ])>
                                                    <svg class="h-3.5 w-3.5 transition-transform"
                                                        :class="open && 'rotate-180'" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                    Notes
                                                </button>
                                            </td>
                                        </tr>
                                        <tr x-show="open" x-cloak class="bg-slate-50/80 dark:bg-slate-800/30">
                                            <td></td>
                                            <td colspan="3" class="!py-3">
                                                <div
                                                    class="flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3">
                                                    <label
                                                        class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 shrink-0 cursor-pointer">
                                                        <input type="checkbox"
                                                            wire:model.live="entries.{{ $student->id }}.went_out"
                                                            class="rounded text-green-700">
                                                        Left during class
                                                    </label>
                                                    <input type="text"
                                                        wire:model.blur="entries.{{ $student->id }}.remarks"
                                                        placeholder="Add a note…"
                                                        class="input-field text-xs py-1.5 flex-1 min-w-0">
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    @endif
                                @empty
                                    <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center py-16">
                                            <p class="text-sm text-slate-500">No students match your filters.</p>
                                            @if ($studentSearch || $statusFilter)
                                                <button type="button"
                                                    wire:click="$set('studentSearch', ''); $set('statusFilter', '')"
                                                    class="mt-2 text-xs text-green-700 hover:underline">Clear filters</button>
                                            @endif
                                        </td>
                                    </tr>
                                    </tbody>
                                @endforelse
                        </table>
                    </div>

                    <div
                        class="px-4 sm:px-5 py-3 border-t border-surface-border dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 flex items-center justify-between xl:hidden">
                        <span class="text-xs text-slate-500">{{ $attendanceStats['rate'] }}% in class</span>
                        <button type="button" wire:click="save" wire:loading.attr="disabled"
                            class="btn-primary text-sm py-2">
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                </div>
            @elseif ($section && $classScheduleId)
                <div class="panel text-center py-16 text-slate-500">
                    <p class="text-sm">No students enrolled in this class.</p>
                </div>
            @else
                <div class="panel border-dashed text-center py-20">
                    <svg class="mx-auto h-12 w-12 mb-3 text-slate-300 dark:text-slate-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <p class="text-sm font-medium text-slate-500">Student list appears here</p>
                    <p class="text-xs text-slate-400 mt-1">Select section and class above to begin</p>
                </div>
            @endif
        </div>
    </div>
</div>
