<div x-data="scheduleTutorial()" x-init="init()">
    <x-page-header title="Class Schedules" subtitle="Build and manage weekly class schedules">
        <x-slot name="actions">
            <button type="button" @click="start(true)" class="btn-secondary text-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Tutorial
            </button>
        </x-slot>
    </x-page-header>
    <x-settings-academic-nav />

    {{-- Top filters --}}
    <div class="panel mb-5" data-schedule-tour="filters">
        <div @class([
            'grid sm:grid-cols-2 gap-3 items-end',
            'lg:grid-cols-[repeat(5,minmax(0,1fr))_auto]' => !$showStrandFilter,
            'lg:grid-cols-[repeat(6,minmax(0,1fr))_auto]' => $showStrandFilter,
        ])>
            <div>
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Academic Year</label>
                <select wire:model.live="academicYearId" class="select-field">
                    @foreach ($years as $year)
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
                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Semester</label>
                <select wire:model.live="semester" class="select-field">
                    @foreach ($semesters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
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
            @if ($showStrandFilter)
                <div>
                    <label class="text-[11px] font-medium text-slate-500 mb-1 block">Strand</label>
                    <select wire:model.live="strand" class="select-field">
                        <option value="">All strands</option>
                        @foreach ($strands as $strandOption)
                            <option value="{{ $strandOption->id }}">
                                {{ $strandOption->code }} — {{ $strandOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="button" wire:click="resetFilters" class="btn-secondary text-sm whitespace-nowrap">
                Reset filters
            </button>
        </div>
    </div>

    @if ($conflictCount > 0)
        <div
            class="panel mb-5 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-start gap-3 flex-1">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                        {{ $conflictCount }} conflicting {{ str('entry')->plural($conflictCount) }} detected
                    </p>
                    <p class="text-xs text-amber-800/80 dark:text-amber-200/80 mt-0.5">
                        Overlapping times for the same section, teacher, or room.
                    </p>
                </div>
            </div>
            <label
                class="inline-flex items-center gap-2 text-sm text-amber-900 dark:text-amber-100 shrink-0 cursor-pointer">
                <input type="checkbox" wire:model.live="showConflictsOnly" class="rounded text-amber-600">
                Show conflicts only
            </label>
        </div>
    @endif

    <div class="grid lg:grid-cols-[minmax(380px,420px)_1fr] gap-5">
        {{-- Left sidebar: Create / Edit --}}
        <form wire:submit="save" id="schedule-form" class="panel space-y-4 h-fit lg:sticky lg:top-24">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Create / Edit Schedule</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $editingId ? 'Update the selected class entry' : 'Add classes to the weekly schedule' }}
                    </p>
                </div>
                @if ($editingId)
                    <button type="button" wire:click="resetForm"
                        class="text-xs text-slate-500 hover:text-brand-600">Cancel</button>
                @endif
            </div>

            <div class="space-y-3" data-schedule-tour="class-details">
                @if ($showFormStrandField)
                    <x-schedule-select-field label="Strand" panel="strand">
                        <select wire:model.live="formCourseId" class="select-field">
                            <option value="">Select strand…</option>
                            @foreach ($strands as $strandOption)
                                <option value="{{ $strandOption->id }}">
                                    {{ $strandOption->code }} — {{ $strandOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-schedule-select-field>
                    @if ($strands->isEmpty())
                        <p class="text-xs text-amber-600 -mt-1">
                            No strands found. Use the <strong>+</strong> button to add one.
                        </p>
                    @endif
                @endif

                <x-schedule-select-field label="Section" panel="section" :error="$errors->get('sectionId')">
                    <select wire:model.live="sectionId" class="select-field" @disabled($showFormStrandField && !$formCourseId)>
                        <option value="">Select section…</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->display_label }}</option>
                        @endforeach
                    </select>
                </x-schedule-select-field>

                <x-schedule-select-field label="Subject" panel="subject" :error="$errors->get('subjectId')">
                    <select wire:model.live="subjectId" class="select-field" {{ $sectionId ? '' : 'disabled' }}>
                        <option value="">Select subject…</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} — {{ $subject->name }}</option>
                        @endforeach
                    </select>
                </x-schedule-select-field>

                <x-schedule-select-field label="Teacher" panel="teacher" :error="$errors->get('teacherId')">
                    <select wire:model.live="teacherId" class="select-field">
                        <option value="">Select teacher…</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                        @endforeach
                    </select>
                </x-schedule-select-field>

                <x-schedule-select-field label="Room" panel="room" :optional="true">
                    <select wire:model.live="roomId" class="select-field">
                        <option value="">No room assigned</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->display_name }}</option>
                        @endforeach
                    </select>
                </x-schedule-select-field>
            </div>

            {{-- Time range --}}
            <div class="pt-3 border-t border-surface-border dark:border-slate-800 space-y-3"
                data-schedule-tour="time-days">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Time Range</label>

                @unless ($editingId)
                    <div class="flex flex-col gap-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="timeMode" value="same" class="text-brand-600">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Apply same time every day</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="timeMode" value="custom" class="text-brand-600">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Set custom time per day</span>
                        </label>
                    </div>
                @else
                    <p class="text-xs text-slate-500">Select one day and set the time for this entry.</p>
                @endunless

                @if ($timeMode === 'same' && ! $editingId)
                    <div class="space-y-2">
                        <span class="text-xs text-slate-500">Time slots (applied to each selected day)</span>
                        @foreach ($sharedTimeSlots as $index => $slot)
                            <div wire:key="shared-slot-{{ $index }}"
                                class="flex items-center gap-2 rounded-xl px-2 py-2 bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800">
                                <input wire:model.live="sharedTimeSlots.{{ $index }}.starts_at" type="time"
                                    class="input-field text-sm py-1.5 flex-1 min-w-0">
                                <span class="text-slate-400 text-xs shrink-0">–</span>
                                <input wire:model.live="sharedTimeSlots.{{ $index }}.ends_at" type="time"
                                    class="input-field text-sm py-1.5 flex-1 min-w-0">
                                @if (count($sharedTimeSlots) > 1)
                                    <button type="button" wire:click="removeSharedTimeSlot({{ $index }})"
                                        class="text-xs font-medium text-red-600 hover:text-red-700 shrink-0 px-1">
                                        Remove
                                    </button>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" wire:click="addSharedTimeSlot"
                            class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Add time slot
                        </button>
                    </div>
                @elseif ($editingId)
                    <div class="grid grid-cols-2 gap-2">
                        <input wire:model.live="defaultStartsAt" type="time" class="input-field text-sm">
                        <input wire:model.live="defaultEndsAt" type="time" class="input-field text-sm">
                    </div>
                @endif

                {{-- Day toggles --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-slate-500">Days</span>
                        @unless ($editingId)
                            <span class="text-xs text-slate-400">{{ $selectedDayCount }} selected</span>
                        @endunless
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($days as $value => $label)
                            @php
                                $slot = $daySlots[$value] ?? ['enabled' => false];
                                $dayHasConflict =
                                    ($slot['enabled'] ?? false) &&
                                    collect($formConflicts)->contains(fn($c) => $c['day'] === (int) $value);
                            @endphp
                            <button type="button" wire:click="toggleDay({{ $value }})"
                                @class([
                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition border',
                                    'bg-brand-600 text-white border-brand-600 shadow-sm' =>
                                        ($slot['enabled'] ?? false) && !$dayHasConflict,
                                    'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700' => $dayHasConflict,
                                    'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-surface-border dark:border-slate-700 hover:border-brand-300' => !(
                                        $slot['enabled'] ?? false
                                    ),
                                ])>
                                {{ \App\Enums\DayOfWeek::from($value)->shortLabel() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Custom per-day times --}}
                @if ($timeMode === 'custom' && ! $editingId)
                    <div class="space-y-3 max-h-56 overflow-y-auto pr-1">
                        @foreach ($days as $value => $label)
                            @php
                                $slot = $daySlots[$value] ?? ['enabled' => false, 'times' => []];
                            @endphp
                            @if ($slot['enabled'])
                                <div wire:key="custom-day-{{ $value }}"
                                    class="rounded-xl px-3 py-2.5 bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            {{ \App\Enums\DayOfWeek::from($value)->label() }}
                                        </span>
                                        <button type="button" wire:click="addDayTimeSlot({{ $value }})"
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 hover:text-brand-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add time
                                        </button>
                                    </div>
                                    @foreach ($slot['times'] ?? [] as $timeIndex => $time)
                                        <div wire:key="custom-day-{{ $value }}-time-{{ $timeIndex }}"
                                            class="flex items-center gap-2">
                                            <input
                                                wire:model.live="daySlots.{{ $value }}.times.{{ $timeIndex }}.starts_at"
                                                type="time" class="input-field text-sm py-1.5 flex-1 min-w-0">
                                            <span class="text-slate-400 text-xs shrink-0">–</span>
                                            <input
                                                wire:model.live="daySlots.{{ $value }}.times.{{ $timeIndex }}.ends_at"
                                                type="time" class="input-field text-sm py-1.5 flex-1 min-w-0">
                                            @if (count($slot['times'] ?? []) > 1)
                                                <button type="button"
                                                    wire:click="removeDayTimeSlot({{ $value }}, {{ $timeIndex }})"
                                                    class="text-xs font-medium text-red-600 hover:text-red-700 shrink-0 px-1">
                                                    Remove
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @unless ($editingId)
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" wire:click="selectWeekdays"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-surface-border dark:border-slate-700 hover:border-brand-300 transition">
                            Mon–Fri
                        </button>
                        <button type="button" wire:click="clearDays"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium text-slate-500 hover:text-red-600 transition">
                            Clear all
                        </button>
                    </div>
                @endunless

                <x-input-error :messages="$errors->get('daySlots')" class="mt-1" />
                <x-input-error :messages="$errors->get('conflicts')" class="mt-1" />

                @if ($formConflicts !== [])
                    <div
                        class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 space-y-1.5">
                        <p class="text-xs font-semibold text-amber-900 dark:text-amber-100">Scheduling conflicts</p>
                        @foreach (collect($formConflicts)->unique('message') as $conflict)
                            <p class="text-xs text-amber-800 dark:text-amber-200 flex items-start gap-1.5">
                                <span @class([
                                    'shrink-0 mt-0.5 inline-block h-1.5 w-1.5 rounded-full',
                                    'bg-red-500' => $conflict['type'] === 'section',
                                    'bg-purple-500' => $conflict['type'] === 'teacher',
                                    'bg-orange-500' => $conflict['type'] === 'room',
                                ])></span>
                                {{ $conflict['message'] }}
                            </p>
                        @endforeach
                    </div>
                @elseif ($sectionId && $subjectId && $teacherId && $selectedDayCount > 0)
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        No conflicts for selected days
                    </p>
                @endif
            </div>

            {{-- Quick actions --}}
            @unless ($editingId)
                <div class="pt-3 border-t border-surface-border dark:border-slate-800">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-2">Quick Actions</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="clearDays" class="btn-secondary text-xs py-2">Clear
                            all</button>
                        <button type="button" wire:click="copyLastSchedule" class="btn-secondary text-xs py-2">Copy last
                            schedule</button>
                        <button type="button" wire:click="showComingSoon('CSV import')"
                            class="btn-secondary text-xs py-2">Import from CSV</button>
                        <button type="button" wire:click="showComingSoon('Templates')"
                            class="btn-secondary text-xs py-2">Templates</button>
                    </div>
                </div>
            @endunless

            <button type="submit" data-schedule-tour="submit" @class([
                'btn-primary text-sm w-full',
                'opacity-60 cursor-not-allowed' => $formConflicts !== [],
            ])
                @disabled($formConflicts !== [])>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                @if ($editingId)
                    Update Schedule
                @else
                    Add to Schedule
                @endif
            </button>

            <div class="rounded-xl bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 p-3">
                <p class="text-xs text-sky-800 dark:text-sky-200">
                    <span class="font-semibold">Tip:</span>
                    Add multiple time slots per day for subjects taught morning and afternoon. Use <strong>Edit</strong> on any row to update a single entry.
                </p>
            </div>
        </form>

        {{-- Right: Schedule Overview --}}
        <div class="space-y-4">
            <div class="panel overflow-hidden p-0" data-schedule-tour="overview">
                <div
                    class="px-5 py-4 border-b border-surface-border dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Schedule Overview</h3>
                        <p class="text-xs text-slate-500 mt-0.5">All scheduled classes</p>
                    </div>
                    <div
                        class="flex items-center gap-2 text-[10px] font-medium uppercase tracking-wide text-slate-400">
                        <span class="inline-flex items-center gap-1"><span
                                class="h-2 w-2 rounded-full bg-amber-500"></span> Conflict</span>
                    </div>
                </div>

                {{-- Stats cards --}}
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 p-4 border-b border-surface-border dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30">
                    <div
                        class="rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total Classes</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ number_format($stats['total_classes']) }}</p>
                    </div>
                    <div
                        class="rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Weekly Hours</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ $stats['weekly_hours_label'] }}</p>
                    </div>
                    <div
                        class="rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Teachers</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ number_format($stats['teachers']) }}</p>
                    </div>
                    <div
                        class="rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Subjects</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ number_format($stats['subjects']) }}</p>
                    </div>
                    <div
                        class="rounded-xl bg-white dark:bg-slate-900 border border-surface-border dark:border-slate-800 p-3 col-span-2 sm:col-span-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Rooms Used</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">
                            {{ number_format($stats['rooms_used']) }}</p>
                    </div>
                </div>

                {{-- Schedule table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-surface-border dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/40 text-left">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Subject</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Day
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Time</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 hidden md:table-cell">
                                    Room</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">
                                    Teacher</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border dark:divide-slate-800">
                            @forelse ($scheduleGroups as $group)
                                @php
                                    $schedule = $group['schedule'];
                                    $startTime = \Illuminate\Support\Carbon::parse($schedule->starts_at)->format(
                                        'h:i A',
                                    );
                                    $endTime = \Illuminate\Support\Carbon::parse($schedule->ends_at)->format('h:i A');
                                @endphp
                                <tr wire:key="schedule-group-{{ implode('-', $group['schedule_ids']) }}" @class([
                                    'bg-brand-50/60 dark:bg-brand-900/20' => $group['is_editing'],
                                    'bg-amber-50/60 dark:bg-amber-900/10' =>
                                        $group['has_conflict'] && ! $group['is_editing'],
                                ])>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ $schedule->subject?->code }}</p>
                                        <p class="text-xs text-slate-500 truncate max-w-[10rem]">
                                            {{ $schedule->subject?->name }}</p>
                                        @if ($schedule->section?->course)
                                            <span
                                                class="inline-flex mt-1 items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-200">
                                                {{ $schedule->section->course->code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-slate-800 dark:text-slate-200">
                                        <p class="font-medium">{{ $group['days_label'] }}</p>
                                        @if ($group['count'] > 1)
                                            <div class="flex flex-wrap gap-1 mt-1.5">
                                                @foreach ($group['schedules'] as $daySchedule)
                                                    <span wire:key="day-badge-{{ $daySchedule->id }}"
                                                        class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ $daySchedule->day_of_week->shortLabel() }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top whitespace-nowrap font-medium text-slate-900 dark:text-white">
                                        {{ $startTime }} – {{ $endTime }}
                                    </td>
                                    <td class="px-4 py-3 align-top hidden md:table-cell text-slate-700 dark:text-slate-300">
                                        {{ $schedule->room?->display_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 align-top hidden lg:table-cell text-slate-700 dark:text-slate-300">
                                        {{ $schedule->teacher?->full_name }}
                                    </td>
                                    <td class="px-4 py-3 align-top text-right">
                                        @if ($group['count'] === 1)
                                            <div class="flex justify-end gap-2 whitespace-nowrap">
                                                <button type="button"
                                                    wire:click="edit({{ $group['schedule_ids'][0] }})"
                                                    class="text-xs font-medium text-brand-600 hover:underline">Edit</button>
                                                <button type="button"
                                                    wire:click="delete({{ $group['schedule_ids'][0] }})"
                                                    wire:confirm="Delete this schedule entry?"
                                                    class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                            </div>
                                        @else
                                            <details class="relative inline-block text-left group/details">
                                                <summary
                                                    class="list-none cursor-pointer text-xs font-medium text-brand-600 hover:underline marker:content-none">
                                                    Manage ({{ $group['count'] }})
                                                </summary>
                                                <div
                                                    class="mt-2 rounded-lg border border-surface-border dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm text-left min-w-[9rem]">
                                                    @foreach ($group['schedules'] as $daySchedule)
                                                        <div wire:key="manage-{{ $daySchedule->id }}"
                                                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs border-b border-surface-border/60 dark:border-slate-800 last:border-0">
                                                            <span class="font-medium text-slate-700 dark:text-slate-300">
                                                                {{ $daySchedule->day_of_week->shortLabel() }}
                                                            </span>
                                                            <span class="flex gap-2 shrink-0">
                                                                <button type="button"
                                                                    wire:click="edit({{ $daySchedule->id }})"
                                                                    class="font-medium text-brand-600 hover:underline">Edit</button>
                                                                <button type="button"
                                                                    wire:click="delete({{ $daySchedule->id }})"
                                                                    wire:confirm="Delete this schedule entry?"
                                                                    class="font-medium text-red-600 hover:underline">Delete</button>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                    <div class="px-3 py-2 border-t border-surface-border dark:border-slate-800">
                                                        <button type="button"
                                                            wire:click="deleteGroup(@js($group['schedule_ids']))"
                                                            wire:confirm="Delete all {{ $group['count'] }} entries for this subject and time?"
                                                            class="text-xs font-medium text-red-600 hover:underline">
                                                            Delete all
                                                        </button>
                                                    </div>
                                                </div>
                                            </details>
                                        @endif
                                        @if ($group['has_conflict'] && $group['conflicts'] !== [])
                                            <div class="mt-1 space-y-0.5 text-right">
                                                @foreach ($group['conflicts'] as $msg)
                                                    <p class="text-[11px] text-amber-800 dark:text-amber-200">
                                                        {{ $msg }}</p>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                        @if ($showConflictsOnly)
                                            No conflicting schedule entries found.
                                        @else
                                            No classes scheduled yet. Use the form to add entries.
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

    @if ($quickAddPanel)
        @include('livewire.settings.academic.partials.schedule-quick-add')
    @endif

    <x-schedule-tutorial />
</div>

@script
    <script>
        $wire.on('scroll-to-schedule-form', () => {
            document.getElementById('schedule-form')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    </script>
@endscript
