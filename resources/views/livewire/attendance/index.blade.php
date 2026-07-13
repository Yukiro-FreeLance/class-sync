<div>
    <x-page-header title="Attendance" subtitle="Campus gate check-in or class session marking">
        <x-slot name="actions">
            @can('create', \App\Models\AttendanceRecord::class)
                <a href="{{ route('attendance.bulk') }}" wire:navigate class="btn-primary">Bulk Class Attendance</a>
            @endcan
            @can('update', \App\Models\Setting::class)
                <a href="{{ route('settings.attendance') }}" wire:navigate class="btn-secondary">Configure</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="flex gap-2 mb-6">
        @unless ($isTeacherScoped ?? false)
            <button type="button" wire:click="$set('mode', 'gate')" @class([
                'px-4 py-2 rounded-xl text-sm font-medium transition',
                'bg-green-700 text-white shadow-sm' => $mode === 'gate',
                'btn-secondary' => $mode !== 'gate',
            ])>Campus Gate</button>
        @endunless
        <button type="button" wire:click="$set('mode', 'class')" @class([
            'px-4 py-2 rounded-xl text-sm font-medium transition',
            'bg-green-700 text-white shadow-sm' => $mode === 'class',
            'btn-secondary' => $mode !== 'class',
        ])>Class Session</button>
    </div>

    @if (($isTeacherScoped ?? false) && $sections->isEmpty())
        <div class="panel mb-6 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                No class schedules are assigned to you. Ask the registrar to assign you as the teacher on the class
                schedule.
            </p>
        </div>
    @elseif (($isTeacherScoped ?? false) && $section && $classSchedules->isEmpty())
        <div class="panel mb-6 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                No classes are scheduled for {{ $weekdayLabel }} on this date in the selected section.
            </p>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        @if ($mode === 'gate')
            <form wire:submit="recordGate" class="panel">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Campus Check-in / Check-out</h3>

                <div class="mb-4">
                    <x-input-label value="Date" />
                    <x-text-input wire:model="date" type="date" class="mt-1 block w-full" />
                </div>

                <div class="mb-4 relative">
                    <x-input-label value="Search Student" />
                    <x-text-input wire:model.live.debounce.300ms="search" type="search"
                        placeholder="Type name or ID..." class="mt-1 block w-full" />
                    @if ($students->isNotEmpty())
                        <div class="absolute z-10 w-full mt-1 panel shadow-lg overflow-hidden p-0">
                            @foreach ($students as $student)
                                <button type="button" wire:click="selectStudent({{ $student->id }})"
                                    class="w-full text-left px-4 py-2.5 hover:bg-brand-50 dark:hover:bg-brand-900/20 text-sm border-b border-surface-border dark:border-slate-800 last:border-0">
                                    <span class="font-medium">{{ $student->list_name }}</span>
                                    <span
                                        class="text-slate-500 ml-2 font-mono text-xs">{{ $student->student_number }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($selectedStudent)
                    <div
                        class="mb-4 p-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-800">
                        <p class="font-medium">{{ $selectedStudent->list_name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $selectedStudent->student_number }}</p>
                    </div>
                @endif

                <div class="mb-4">
                    <x-input-label value="Action" />
                    <select wire:model="action" class="mt-1 select-field">
                        <option value="in">Check In</option>
                        <option value="out">Check Out</option>
                    </select>
                </div>

                <div class="mb-4">
                    <x-input-label value="Remarks" />
                    <textarea wire:model="remarks" rows="2" class="mt-1 input-field" placeholder="Optional notes"></textarea>
                </div>

                <x-input-error :messages="$errors->get('selectedStudentId')" class="mb-4" />

                <x-primary-button type="submit">Record Campus Attendance</x-primary-button>
            </form>

            <div class="panel">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Campus Records</h3>
                <div class="space-y-2 max-h-[32rem] overflow-y-auto">
                    @forelse ($todayRecords as $record)
                        <div
                            class="flex items-center justify-between py-2.5 border-b border-surface-border dark:border-slate-800 last:border-0">
                            <div>
                                <p class="text-sm font-medium">{{ $record->student?->list_name ?? 'Unknown' }}</p>
                                <span @class([
                                    'text-xs font-medium',
                                    'text-emerald-600' => $record->time_in && !$record->time_out,
                                    'text-slate-500' => $record->time_out,
                                ])>
                                    {{ $record->status?->label() }}
                                    @if ($record->time_out)
                                        · Checked out
                                    @elseif ($record->time_in)
                                        · On campus
                                    @endif
                                </span>
                            </div>
                            <span
                                class="text-xs text-slate-500">{{ $record->time_in ? \Illuminate\Support\Str::substr($record->time_in, 0, 5) : '—' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No campus records for this date.</p>
                    @endforelse
                </div>
            </div>
        @else
            <form wire:submit="recordClass" class="panel space-y-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Class Session Attendance</h3>
                <p class="text-sm text-slate-500">Uses the same subject schedule and remarks as bulk attendance.</p>

                <x-attendance-class-filters :departments="$departments" :grades="$grades" :sections="$sections" :class-schedules="$classSchedules"
                    :selected-schedule="$selectedSchedule" :weekday-label="$weekdayLabel" :strands="$strands"
                    :show-strand-filter="$showStrandFilter" :department-id="$department" :grade-id="$grade"
                    :strand-id="$strand" :section-id="$section" />

                <div class="relative">
                    <x-input-label value="Search Student" />
                    <x-text-input wire:model.live.debounce.300ms="search" type="search"
                        placeholder="Student in selected class..." class="mt-1 block w-full"
                        {{ $classScheduleId ? '' : 'disabled' }} />
                    @if ($students->isNotEmpty())
                        <div class="absolute z-10 w-full mt-1 panel shadow-lg overflow-hidden p-0">
                            @foreach ($students as $student)
                                <button type="button" wire:click="selectStudent({{ $student->id }})"
                                    class="w-full text-left px-4 py-2.5 hover:bg-brand-50 dark:hover:bg-brand-900/20 text-sm border-b border-surface-border dark:border-slate-800 last:border-0">
                                    <span class="font-medium">{{ $student->list_name }}</span>
                                    <span
                                        class="text-slate-500 ml-2 font-mono text-xs">{{ $student->student_number }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($selectedStudent)
                    <div
                        class="p-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-800">
                        <p class="font-medium">{{ $selectedStudent->list_name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $selectedStudent->student_number }}</p>
                    </div>
                @endif

                <div>
                    <x-input-label value="Attendance Status" />
                    <select wire:model="classRemarkId" class="mt-1 select-field"
                        {{ $classScheduleId ? '' : 'disabled' }}>
                        @foreach ($attendanceRemarks as $remark)
                            <option value="{{ $remark->id }}">{{ $remark->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="classWentOut" class="rounded text-green-700"
                            {{ $classScheduleId ? '' : 'disabled' }}>
                        Left during class
                    </label>
                </div>

                <div>
                    <x-input-label value="Remarks" />
                    <textarea wire:model="classRemarks" rows="2" class="mt-1 input-field" placeholder="Optional note"></textarea>
                </div>

                <x-input-error :messages="$errors->get('selectedStudentId')" class="mb-2" />
                <x-input-error :messages="$errors->get('classScheduleId')" class="mb-2" />

                @if ($classScheduleId)
                    <x-primary-button type="submit">Save Class Attendance</x-primary-button>
                @else
                    <x-primary-button type="button" disabled>Save Class Attendance</x-primary-button>
                @endif
            </form>

            <div class="panel">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Class Records</h3>
                @if ($selectedSchedule)
                    <p class="text-sm text-slate-500 mb-4">{{ $selectedSchedule->display_label }}</p>
                @endif
                <div class="space-y-2 max-h-[32rem] overflow-y-auto">
                    @forelse ($classLogs as $log)
                        <div
                            class="flex items-center justify-between py-2.5 border-b border-surface-border dark:border-slate-800 last:border-0">
                            <div>
                                <p class="text-sm font-medium">{{ $log->student?->list_name ?? 'Unknown' }}</p>
                                <span class="text-xs font-medium"
                                    style="color: {{ $log->remark?->color ?? '#64748b' }}">
                                    {{ $log->remark?->label }}
                                    @if ($log->went_out_at && !$log->returned_at)
                                        · Out
                                    @endif
                                </span>
                            </div>
                            @if ($log->remarks)
                                <span class="text-xs text-slate-400 max-w-[8rem] truncate">{{ $log->remarks }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">
                            @if ($classScheduleId)
                                No class attendance saved yet for this session.
                            @else
                                Select a class to view records.
                            @endif
                        </p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
