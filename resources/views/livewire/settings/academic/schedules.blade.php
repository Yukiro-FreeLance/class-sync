<div>
    <x-page-header title="Class Schedules" subtitle="Assign subjects, teachers, and rooms to sections" />
    <x-settings-academic-nav />

    <div class="panel mb-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Academic Year</label>
            <select wire:model.live="academicYearId" class="select-field">
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Semester</label>
            <select wire:model.live="semester" class="select-field">
                @foreach ($semesters as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
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
            <select wire:model.live="grade" class="select-field">
                <option value="">All grades</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($conflictCount > 0)
        <div class="panel mb-5 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-start gap-3 flex-1">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                        {{ $conflictCount }} conflicting {{ str('entry')->plural($conflictCount) }} detected
                    </p>
                    <p class="text-xs text-amber-800/80 dark:text-amber-200/80 mt-0.5">
                        Overlapping times for the same section, teacher, or room. Review highlighted rows below.
                    </p>
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-amber-900 dark:text-amber-100 shrink-0 cursor-pointer">
                <input type="checkbox" wire:model.live="showConflictsOnly" class="rounded text-amber-600">
                Show conflicts only
            </label>
        </div>
    @endif

    <div class="grid lg:grid-cols-[minmax(400px,460px)_1fr] gap-5">
        <form wire:submit="save" class="panel space-y-4 h-fit lg:sticky lg:top-24">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Edit' : 'Add' }} Schedule</h3>
                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="text-xs text-slate-500 hover:text-brand-600">Cancel edit</button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Section</label>
                    <select wire:model.live="sectionId" class="select-field">
                        <option value="">Select section…</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">
                                {{ $section->gradeLevel?->name }} — {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sectionId')" class="mt-1" />
                </div>

                @if ($formSemesters !== [])
                    <div>
                        <label class="text-xs font-medium text-slate-500 mb-1 block">Semester</label>
                        <select wire:model.live="semester" class="select-field">
                            @foreach ($formSemesters as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Subject</label>
                    <select
                        wire:model.live="subjectId"
                        class="select-field"
                        {{ $sectionId ? '' : 'disabled' }}
                    >
                        <option value="">Select subject…</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->code }} — {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('subjectId')" class="mt-1" />
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Teacher</label>
                    <select wire:model.live="teacherId" class="select-field">
                        <option value="">Select teacher…</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->full_name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('teacherId')" class="mt-1" />
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-medium text-slate-500 mb-1 block">
                        Room <span class="font-normal">(optional)</span>
                    </label>
                    <select wire:model.live="roomId" class="select-field">
                        <option value="">No room assigned</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">
                                {{ $room->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="pt-3 border-t border-surface-border dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        {{ $editingId ? 'Day & Time' : 'Days & Times' }}
                    </label>
                    @unless ($editingId)
                        <span class="text-xs text-slate-400">{{ $selectedDayCount }} selected</span>
                    @endunless
                </div>

                @unless ($editingId)
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-surface-border dark:border-slate-800 p-3 space-y-2">
                        <p class="text-xs text-slate-500">Default time for new selections</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input wire:model.live="defaultStartsAt" type="time" class="input-field text-sm">
                            <input wire:model.live="defaultEndsAt" type="time" class="input-field text-sm">
                        </div>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button type="button" wire:click="selectWeekdays" class="px-2 py-1 rounded-lg text-xs font-medium bg-white dark:bg-slate-800 border border-surface-border dark:border-slate-700 hover:border-brand-300 transition">
                                Mon–Fri
                            </button>
                            <button type="button" wire:click="applyDefaultTimes" class="px-2 py-1 rounded-lg text-xs font-medium bg-white dark:bg-slate-800 border border-surface-border dark:border-slate-700 hover:border-brand-300 transition">
                                Apply to selected
                            </button>
                            <button type="button" wire:click="clearDays" class="px-2 py-1 rounded-lg text-xs font-medium text-slate-500 hover:text-red-600 transition">
                                Clear all
                            </button>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-500">Select one day for this entry.</p>
                @endunless

                <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                    @foreach ($days as $value => $label)
                        @php
                            $slot = $daySlots[$value] ?? ['enabled' => false, 'starts_at' => '08:00', 'ends_at' => '09:00'];
                            $dayHasConflict = $slot['enabled'] && collect($formConflicts)->contains(fn ($c) => $c['day'] === (int) $value);
                        @endphp
                        <div wire:key="day-slot-{{ $value }}"
                            @class([
                                'flex items-center gap-2 rounded-xl px-2 py-2 transition border',
                                'bg-brand-50 dark:bg-brand-900/20 border-brand-200 dark:border-brand-800' => $slot['enabled'] && ! $dayHasConflict,
                                'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700' => $dayHasConflict,
                                'border-transparent hover:bg-slate-50 dark:hover:bg-slate-900/40' => ! $slot['enabled'],
                            ])>
                            <label class="inline-flex items-center gap-2 shrink-0 w-[6.5rem] cursor-pointer">
                                <input type="checkbox" wire:model.live="daySlots.{{ $value }}.enabled" class="rounded text-brand-600">
                                <span @class([
                                    'text-sm',
                                    'font-medium text-slate-900 dark:text-white' => $slot['enabled'],
                                    'text-slate-600 dark:text-slate-400' => ! $slot['enabled'],
                                ])>{{ $label }}</span>
                            </label>
                            <input wire:model.live="daySlots.{{ $value }}.starts_at" type="time"
                                class="input-field text-sm py-1.5 flex-1 min-w-0" {{ $slot['enabled'] ? '' : 'disabled' }}>
                            <span class="text-slate-400 text-xs shrink-0">–</span>
                            <input wire:model.live="daySlots.{{ $value }}.ends_at" type="time"
                                class="input-field text-sm py-1.5 flex-1 min-w-0" {{ $slot['enabled'] ? '' : 'disabled' }}>
                        </div>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('daySlots')" class="mt-1" />
                <x-input-error :messages="$errors->get('conflicts')" class="mt-1" />

                @if ($formConflicts !== [])
                    <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 space-y-1.5">
                        <p class="text-xs font-semibold text-amber-900 dark:text-amber-100">Scheduling conflicts</p>
                        @foreach (collect($formConflicts)->unique('message') as $conflict)
                            <p class="text-xs text-amber-800 dark:text-amber-200 flex items-start gap-1.5">
                                <span @class([
                                    'shrink-0 mt-0.5 inline-block h-1.5 w-1.5 rounded-full',
                                    'bg-red-500' => $conflict['type'] === 'section',
                                    'bg-orange-500' => $conflict['type'] === 'teacher',
                                    'bg-purple-500' => $conflict['type'] === 'room',
                                ])></span>
                                {{ $conflict['message'] }}
                            </p>
                        @endforeach
                    </div>
                @elseif ($sectionId && $subjectId && $teacherId && $selectedDayCount > 0)
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        No conflicts for selected days
                    </p>
                @endif
            </div>

            <button type="submit" @class(['btn-primary text-sm w-full', 'opacity-60 cursor-not-allowed' => $formConflicts !== []]) @disabled($formConflicts !== [])>
                @if ($editingId)
                    Update Schedule
                @elseif ($selectedDayCount > 1)
                    Save {{ $selectedDayCount }} Entries
                @else
                    Save Schedule
                @endif
            </button>
        </form>

        <div class="panel overflow-hidden p-0">
            <div class="px-5 py-4 border-b border-surface-border dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Scheduled Classes</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $schedules->count() }} entries shown</p>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-medium uppercase tracking-wide text-slate-400">
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span> Section</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-orange-500"></span> Teacher</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-purple-500"></span> Room</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full data-table text-sm">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Room</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $schedule)
                            @php
                                $hasConflict = isset($conflictScheduleIds[$schedule->id]);
                                $rowConflicts = $conflictDetails[$schedule->id] ?? [];
                            @endphp
                            <tr @class([
                                'bg-brand-50/50 dark:bg-brand-900/10' => $editingId === $schedule->id,
                                'bg-amber-50/60 dark:bg-amber-900/10 border-l-4 border-l-amber-400' => $hasConflict && $editingId !== $schedule->id,
                            ])>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $schedule->day_of_week->shortLabel() }}
                                        </span>
                                        @if ($hasConflict)
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" title="{{ implode(' ', $rowConflicts) }}">
                                                !
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="font-mono text-xs whitespace-nowrap">{{ substr((string) $schedule->starts_at, 0, 5) }}–{{ substr((string) $schedule->ends_at, 0, 5) }}</td>
                                <td>{{ $schedule->section?->gradeLevel?->name }} {{ $schedule->section?->name }}</td>
                                <td>
                                    <span class="font-medium">{{ $schedule->subject?->code }}</span>
                                </td>
                                <td class="max-w-[8rem] truncate">{{ $schedule->teacher?->full_name }}</td>
                                <td>{{ $schedule->room?->display_name ?? '—' }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <button wire:click="edit({{ $schedule->id }})" class="text-brand-600 hover:underline text-xs">Edit</button>
                                    <button wire:click="delete({{ $schedule->id }})" wire:confirm="Delete this schedule entry?" class="text-red-600 hover:underline text-xs ml-2">Delete</button>
                                </td>
                            </tr>
                            @if ($hasConflict && $rowConflicts !== [])
                                <tr class="bg-amber-50/40 dark:bg-amber-900/5">
                                    <td colspan="7" class="px-5 py-2 text-xs text-amber-800 dark:text-amber-200">
                                        @foreach ($rowConflicts as $msg)
                                            <span class="inline-flex items-center gap-1 mr-3">
                                                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
                                                </svg>
                                                {{ $msg }}
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-500">
                                    @if ($showConflictsOnly)
                                        No conflicting entries for this filter.
                                    @else
                                        No schedules for this filter.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
