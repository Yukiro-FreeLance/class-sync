<div wire:poll.15s>
    <x-page-header title="Live Monitor" subtitle="Real-time campus attendance">
        <x-slot name="actions">
            <a href="{{ route('attendance.scanner') }}" wire:navigate class="btn-secondary text-sm">Scanner</a>
            <a href="{{ route('attendance.bulk') }}" wire:navigate class="btn-secondary text-sm">Bulk Attendance</a>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="badge-live">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                    Live
                </span>
                <span>{{ $lastUpdated }}</span>
            </div>
        </x-slot>
    </x-page-header>

    {{-- Summary bar --}}
    <div class="panel mb-5 py-3 px-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <span class="text-slate-600 dark:text-slate-400">
            <strong class="text-slate-900 dark:text-white">{{ $stats['recorded_today'] }}</strong> of
            <strong class="text-slate-900 dark:text-white">{{ $stats['total_students'] }}</strong> students recorded
        </span>
        <span class="text-slate-400">·</span>
        <span class="text-teal-600 font-medium">{{ $stats['attendance_rate'] }}% attendance rate</span>
        @if ($stats['not_recorded'] > 0)
            <span class="text-slate-400">·</span>
            <span class="text-amber-600">{{ $stats['not_recorded'] }} not checked in</span>
        @endif
        @if ($stats['visitors_inside'] > 0)
            <span class="text-slate-400">·</span>
            <span class="text-violet-600">{{ $stats['visitors_inside'] }} visitor(s) on campus</span>
        @endif
        @if ($alerts['class_absences'] > 0)
            <span class="text-slate-400">·</span>
            <span class="text-red-600">{{ $alerts['class_absences'] }} class absence(s) today</span>
        @endif
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
        @php
            $cards = [
                ['label' => 'Inside Campus', 'value' => $stats['inside_campus'], 'sub' => 'Checked in, not out', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['label' => 'Present', 'value' => $stats['present'], 'sub' => $stats['present_pct'].'% of total', 'color' => 'text-green-700', 'bg' => 'bg-brand-100 text-green-700 dark:bg-brand-900/30', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Late', 'value' => $stats['late'], 'sub' => $stats['late_pct'].'% of total', 'color' => 'text-amber-600', 'bg' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Absent', 'value' => $stats['absent'], 'sub' => $stats['not_recorded'] > 0 ? "{$stats['explicit_absent']} marked · {$stats['not_recorded']} no record" : $stats['absent_pct'].'% of total', 'color' => 'text-red-600', 'bg' => 'bg-red-100 text-red-600 dark:bg-red-900/30', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Excused', 'value' => $stats['excused'], 'sub' => 'Marked excused', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['label' => 'Checkouts', 'value' => $stats['checkouts'], 'sub' => 'Left campus today', 'color' => 'text-violet-600', 'bg' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30', 'icon' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="stat-card">
                <div class="h-9 w-9 rounded-xl flex items-center justify-center mb-3 {{ $card['bg'] }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $card['icon'] }}" />
                    </svg>
                </div>
                <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ $card['label'] }}</p>
                <p class="text-3xl font-bold mt-1 {{ $card['color'] }}">{{ $card['value'] }}</p>
                <p class="text-[10px] text-slate-400 mt-1 leading-tight">{{ $card['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid xl:grid-cols-12 gap-5">
        {{-- Inside campus --}}
        <div class="panel xl:col-span-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Inside Campus</h3>
                    <p class="text-xs text-slate-500">{{ $inside->count() }} student(s) currently on campus</p>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                <table class="w-full data-table text-sm">
                    <thead class="sticky top-0 bg-white dark:bg-slate-900 z-10">
                        <tr>
                            <th>Student</th>
                            <th class="text-center">In</th>
                            <th class="text-center">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inside as $row)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $row['name'] }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $row['grade'] ?? '—' }}
                                        @if ($row['section']) · {{ $row['section'] }} @endif
                                    </p>
                                </td>
                                <td class="text-center font-mono text-xs">{{ $row['time_in'] }}</td>
                                <td class="text-center text-xs text-emerald-600 font-medium">{{ $row['duration'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-10 text-slate-500">
                                    <p class="font-medium">No students inside</p>
                                    <p class="text-xs mt-1">Check-ins appear here until checkout</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent check-ins --}}
        <div class="panel xl:col-span-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Recent Check-ins</h3>
                    <p class="text-xs text-slate-500">Latest gate entries today</p>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                <table class="w-full data-table text-sm">
                    <thead class="sticky top-0 bg-white dark:bg-slate-900 z-10">
                        <tr>
                            <th>Student</th>
                            <th class="text-center">Time</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCheckIns as $row)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $row['name'] }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $row['student_number'] }}</p>
                                </td>
                                <td class="text-center font-mono text-xs">{{ $row['time'] }}</td>
                                <td class="text-center">
                                    @php $status = $row['status']; @endphp
                                    <span @class([
                                        'inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase',
                                        'bg-emerald-100 text-emerald-700' => $status?->value === 'present',
                                        'bg-amber-100 text-amber-700' => $status?->value === 'late',
                                        'bg-blue-100 text-blue-700' => $status?->value === 'excused',
                                        'bg-red-100 text-red-700' => $status?->value === 'absent',
                                        'bg-slate-100 text-slate-600' => ! in_array($status?->value, ['present', 'late', 'excused', 'absent']),
                                    ])>{{ $status?->label() ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-10 text-slate-500">
                                    <p class="font-medium">No check-ins yet</p>
                                    <p class="text-xs mt-1">Use the scanner or manual attendance to record entries</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right column: classes + not checked in + checkouts --}}
        <div class="xl:col-span-4 space-y-5">
            <div class="panel">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Classes In Session</h3>
                <p class="text-xs text-slate-500 mb-3">{{ count($classesInSession) }} active now</p>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse ($classesInSession as $class)
                        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 px-3 py-2">
                            <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $class['subject'] }}</p>
                            <p class="text-xs text-slate-500">{{ $class['section'] }} · {{ $class['time'] }}</p>
                            <p class="text-xs text-slate-400">{{ $class['teacher'] }} · {{ $class['room'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-2">No classes in session right now.</p>
                    @endforelse
                </div>
                @if ($upcomingClasses !== [])
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mt-4 mb-2">Up next</p>
                    <div class="space-y-2">
                        @foreach ($upcomingClasses as $class)
                            <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2 text-sm">
                                <span class="font-medium">{{ $class['subject'] }}</span>
                                <span class="text-slate-500 text-xs"> · {{ $class['time'] }} · {{ $class['section'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="panel">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Not Checked In</h3>
                <p class="text-xs text-slate-500 mb-3">Active students with no gate record today</p>
                <div class="space-y-2 max-h-36 overflow-y-auto">
                    @forelse ($notCheckedIn as $student)
                        <div class="flex items-center justify-between text-sm py-1 border-b border-surface-border dark:border-slate-800 last:border-0">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $student['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $student['grade'] ?? '—' }} @if($student['section'])· {{ $student['section'] }}@endif</p>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 shrink-0 ml-2">{{ $student['student_number'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-emerald-600 py-2">All active students are recorded today.</p>
                    @endforelse
                </div>
                @if ($stats['not_recorded'] > $notCheckedIn->count())
                    <p class="text-xs text-slate-400 mt-2">+ {{ $stats['not_recorded'] - $notCheckedIn->count() }} more</p>
                @endif
            </div>

            @if ($recentCheckOuts->isNotEmpty())
                <div class="panel">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Recent Check-outs</h3>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        @foreach ($recentCheckOuts as $row)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium truncate">{{ $row['name'] }}</span>
                                <span class="text-xs font-mono text-slate-500 shrink-0">{{ $row['time_in'] }} → {{ $row['time_out'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
