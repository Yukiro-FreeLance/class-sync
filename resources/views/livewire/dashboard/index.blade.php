<div wire:poll.60s>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">{{ $greeting }}, {{ auth()->user()->name }}! Here's today's attendance overview.</p>
        </div>
        @if ($stats['total_students'] > 0)
            <p class="text-xs text-slate-500">
                {{ $stats['recorded_today'] }} of {{ $stats['total_students'] }} students recorded today
                @if ($stats['not_recorded'] > 0)
                    · <span class="text-amber-600">{{ $stats['not_recorded'] }} not yet recorded</span>
                @endif
            </p>
        @endif
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4 mb-6">
        @php
            $statCards = [
                [
                    'label' => 'Present',
                    'value' => $stats['present'],
                    'pct' => $stats['present_pct'],
                    'trend' => $stats['present_trend'],
                    'hint' => null,
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'iconBg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                    'valueColor' => 'text-emerald-600',
                ],
                [
                    'label' => 'Late',
                    'value' => $stats['late'],
                    'pct' => $stats['late_pct'],
                    'trend' => null,
                    'hint' => null,
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                    'valueColor' => 'text-amber-600',
                ],
                [
                    'label' => 'Absent',
                    'value' => $stats['absent'],
                    'pct' => $stats['absent_pct'],
                    'trend' => null,
                    'hint' => $stats['not_recorded'] > 0 ? "{$stats['explicit_absent']} marked · {$stats['not_recorded']} no record" : null,
                    'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
                    'valueColor' => 'text-red-600',
                ],
                [
                    'label' => 'Excused',
                    'value' => $stats['excused'],
                    'pct' => $stats['excused_pct'],
                    'trend' => null,
                    'hint' => null,
                    'icon' =>
                        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                    'valueColor' => 'text-blue-600',
                ],
                [
                    'label' => 'Visitors',
                    'value' => $stats['visitors'],
                    'pct' => null,
                    'trend' => null,
                    'hint' => null,
                    'icon' =>
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                    'iconBg' => 'bg-brand-100 text-green-700 dark:bg-brand-900/30 dark:text-brand-400',
                    'valueColor' => 'text-green-700',
                ],
                [
                    'label' => 'Checkouts',
                    'value' => $stats['checkouts'],
                    'pct' => null,
                    'trend' => null,
                    'hint' => null,
                    'icon' =>
                        'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
                    'iconBg' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
                    'valueColor' => 'text-violet-600',
                ],
                [
                    'label' => 'Attendance %',
                    'value' => $stats['attendance_percent'] . '%',
                    'pct' => null,
                    'trend' => null,
                    'hint' => 'Present + late + excused',
                    'icon' =>
                        'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'iconBg' => 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
                    'valueColor' => 'text-teal-600',
                ],
            ];
        @endphp

        @foreach ($statCards as $card)
            <div class="stat-card">
                <div class="flex items-start justify-between mb-3">
                    <div class="h-9 w-9 rounded-xl flex items-center justify-center {{ $card['iconBg'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="{{ $card['icon'] }}" />
                        </svg>
                    </div>
                </div>
                <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    {{ $card['label'] }}</p>
                <p class="text-2xl font-bold mt-0.5 {{ $card['valueColor'] }}">{{ $card['value'] }}</p>
                @if ($card['pct'] !== null)
                    <p class="text-xs text-slate-400 mt-1">{{ $card['pct'] }}% of {{ $stats['total_students'] }}</p>
                @endif
                @if ($card['hint'])
                    <p class="text-[10px] text-slate-400 mt-1 leading-tight">{{ $card['hint'] }}</p>
                @endif
                @if ($card['trend'] !== null)
                    <p @class([
                        'text-xs font-medium mt-1',
                        'text-emerald-600' => $card['trend'] >= 0,
                        'text-red-600' => $card['trend'] < 0,
                    ])>
                        {{ $card['trend'] >= 0 ? '+' : '' }}{{ $card['trend'] }}% vs yesterday
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Charts row --}}
    <div class="grid lg:grid-cols-12 gap-6 mb-6">
        <div class="lg:col-span-5 panel" wire:ignore x-data="weeklyAttendanceChart(@js($weeklyLabels), @js($weeklyData))">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Weekly Attendance Trend</h3>
            <p class="text-xs text-slate-500 mb-4">% of active students marked present, late, or excused</p>
            <div class="relative h-[220px] w-full">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="lg:col-span-4 panel" wire:ignore x-data="statusBreakdownChart(@js(array_keys($statusBreakdown)), @js(array_values($statusBreakdown)), @js(array_values($statusBreakdownColors)))">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Status Breakdown</h3>
            <p class="text-xs text-slate-500 mb-4">Today's attendance by status (gate + class)</p>
            <div class="flex items-center gap-4 min-h-[140px]">
                <div class="relative h-36 w-36 shrink-0">
                    <canvas x-ref="canvas"></canvas>
                </div>
                <div class="flex-1 space-y-2">
                    @foreach ($statusBreakdown as $label => $val)
                        @php $total = max(1, array_sum($statusBreakdown)); @endphp
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $statusBreakdownColors[$label] }}"></span>
                                <span class="text-slate-600 dark:text-slate-400 truncate">{{ $label }}</span>
                            </div>
                            <span class="font-semibold text-slate-800 dark:text-white shrink-0 ml-2">
                                {{ $val }} <span class="text-xs font-normal text-slate-400">({{ array_sum($statusBreakdown) > 0 ? round(($val / array_sum($statusBreakdown)) * 100) : 0 }}%)</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 panel flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-900 dark:text-white">Real-time Overview</h3>
                <span class="badge-live">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                    Live
                </span>
            </div>
            <div class="space-y-4 flex-1">
                @foreach ([['label' => 'Currently in Campus', 'value' => $liveOverview['inside_campus'], 'color' => 'text-green-700'], ['label' => 'Classes in Session', 'value' => $liveOverview['classes_in_session'], 'color' => 'text-slate-800 dark:text-white'], ['label' => 'Attendance Alerts', 'value' => $liveOverview['active_alerts'], 'color' => $liveOverview['active_alerts'] > 0 ? 'text-amber-600' : 'text-slate-400'], ['label' => 'Pending Enrollments', 'value' => $liveOverview['pending_approvals'], 'color' => $liveOverview['pending_approvals'] > 0 ? 'text-amber-600' : 'text-slate-800 dark:text-white']] as $item)
                    <div class="flex items-center justify-between py-2 border-b border-surface-border dark:border-slate-800 last:border-0">
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $item['label'] }}</span>
                        <span class="text-lg font-bold {{ $item['color'] }}">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
            {{-- <a href="{{ route('attendance.monitor') }}" wire:navigate
                class="mt-4 text-sm font-medium text-green-700 hover:text-brand-500 flex items-center gap-1">
                Go to Live Monitor
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a> --}}
        </div>
    </div>

    {{-- Bottom row --}}
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Recent Activity</h3>
            <p class="text-xs text-slate-500 mb-4">Latest system actions from audit log</p>
            <div class="space-y-3">
                @forelse ($recentActivity as $activity)
                    @php $style = $dashboardService->activityStyle($activity->action); @endphp
                    <div class="flex items-start gap-3">
                        <div class="h-8 w-8 rounded-lg {{ $style['bg'] }} flex items-center justify-center shrink-0">
                            <svg class="h-4 w-4 {{ $style['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $style['icon'] }}" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-white">
                                {{ $activity->action?->label() ?? 'Activity' }}
                                @if ($activity->user)
                                    <span class="font-normal text-slate-500">· {{ $activity->user->name }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-slate-500 truncate">{{ $activity->description ?? 'System action recorded' }}</p>
                        </div>
                        <span class="text-xs text-slate-400 shrink-0">{{ $activity->created_at?->diffForHumans(short: true) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No recent activity.</p>
                @endforelse
            </div>
            @can('update', \App\Models\Setting::class)
                <a href="{{ route('audit-logs.index') }}" wire:navigate class="mt-4 inline-block text-sm font-medium text-green-700">View audit logs</a>
            @endcan
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Top Absentees (This Week)</h3>
            <p class="text-xs text-slate-500 mb-4">Weekdays with no attendance or marked absent</p>
            <div class="space-y-3">
                @forelse ($topAbsentees as $index => $student)
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-400 w-4">{{ $index + 1 }}</span>
                        <div class="h-9 w-9 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-green-700 dark:text-brand-300 text-sm font-semibold shrink-0">
                            {{ strtoupper(substr($student->first_name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $student->full_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $student->gradeLevel?->name ?? '—' }}
                                @if ($student->section) &middot; {{ $student->section->name }} @endif
                            </p>
                            <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-red-500" style="width: {{ round(($student->absences_count / $maxAbsences) * 100) }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-red-600 shrink-0">{{ $student->absences_count }}d</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No absences recorded this week — great attendance!</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Today's Class Schedule</h3>
            <p class="text-xs text-slate-500 mb-4">{{ now()->format('l, F j') }}</p>
            <div class="space-y-3">
                @forelse ($todaysSchedules as $schedule)
                    <div class="flex items-start gap-3">
                        <div @class([
                            'flex flex-col items-center justify-center h-12 w-12 rounded-xl shrink-0 border',
                            'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' => $schedule['status'] === 'In session',
                            'bg-brand-50 dark:bg-brand-900/30 border-brand-100 dark:border-brand-800' => $schedule['status'] === 'Upcoming',
                            'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700' => $schedule['status'] === 'Completed',
                        ])>
                            <span class="text-[9px] font-bold uppercase text-slate-500">{{ substr($schedule['status'], 0, 3) }}</span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-none mt-0.5">{{ explode(' – ', $schedule['time'])[0] ?? '' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $schedule['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ $schedule['time'] }} · {{ $schedule['location'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No classes scheduled for today.</p>
                    <a href="{{ route('settings.academic.schedules') }}" wire:navigate class="text-sm font-medium text-green-700">Configure schedules</a>
                @endforelse
            </div>
        </div>
    </div>
</div>
