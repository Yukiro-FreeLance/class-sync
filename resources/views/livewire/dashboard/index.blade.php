<div wire:poll.60s>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">
                {{ $greeting }}, {{ auth()->user()->name }}!
                @if ($isSingleDay)
                    Here's the attendance overview for {{ $periodLabel }}.
                @else
                    Here's the {{ $period }} attendance overview for {{ $periodLabel }}.
                @endif
            </p>
        </div>
        @if ($stats['total_students'] > 0)
            <p class="text-xs text-slate-500 text-right">
                @if ($isSingleDay)
                    {{ $stats['recorded_today'] }} of {{ $stats['total_students'] }} students recorded
                    @if ($stats['not_recorded'] > 0)
                        · <span class="text-amber-600">{{ $stats['not_recorded'] }} not yet recorded</span>
                    @endif
                @else
                    Avg daily attendance <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $stats['avg_daily_attendance'] }}%</span>
                    · {{ $stats['total_students'] }} active students in scope
                @endif
            </p>
        @endif
    </div>

    {{-- Filters --}}
    <div class="panel mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Period</p>
                <div class="inline-flex rounded-xl border border-surface-border dark:border-slate-700 p-0.5 bg-slate-50 dark:bg-slate-800/60">
                    @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('period', '{{ $value }}')"
                            @class([
                                'px-3.5 py-1.5 text-sm font-medium rounded-lg transition',
                                'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' => $period === $value,
                                'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200' => $period !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="min-w-[10rem]">
                <x-input-label value="Date" />
                <x-text-input wire:model.live="date" type="date" class="mt-1 block w-full" />
            </div>

            <div class="min-w-[10rem] flex-1">
                <x-input-label value="Department" />
                <select wire:model.live="department" class="mt-1 select-field">
                    <option value="">All departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[9rem] flex-1">
                <x-input-label value="Level" />
                <select wire:model.live="grade" class="mt-1 select-field" {{ $department ? '' : 'disabled' }}>
                    <option value="">All levels</option>
                    @foreach ($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[9rem] flex-1">
                <x-input-label value="Section" />
                <select wire:model.live="section" class="mt-1 select-field" {{ $grade ? '' : 'disabled' }}>
                    <option value="">All sections</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($hasActiveFilters)
                <button type="button" wire:click="clearFilters" class="btn-secondary shrink-0">
                    Reset
                </button>
            @endif
        </div>
        <p class="text-xs text-slate-400 mt-3">
            Showing {{ $periodLabel }}
            @if ($department || $grade || $section)
                · scoped filters applied
            @endif
        </p>
    </div>

    {{-- Attendance summary --}}
    @php
        $denominator = max(1, $isSingleDay ? $stats['total_students'] : $stats['denominator']);
        $attendedCount = $stats['present'] + $stats['late'] + $stats['excused'];
        $barSegments = [
            ['key' => 'present', 'value' => $stats['present'], 'color' => 'bg-emerald-500', 'label' => 'Present'],
            ['key' => 'late', 'value' => $stats['late'], 'color' => 'bg-amber-500', 'label' => 'Late'],
            ['key' => 'excused', 'value' => $stats['excused'], 'color' => 'bg-blue-500', 'label' => 'Excused'],
            ['key' => 'absent', 'value' => $stats['explicit_absent'] + $stats['half_day'], 'color' => 'bg-red-500', 'label' => 'Absent'],
            ['key' => 'not_recorded', 'value' => $stats['not_recorded'], 'color' => 'bg-slate-300 dark:bg-slate-600', 'label' => 'Not recorded'],
        ];
        $statusMetrics = [
            [
                'key' => 'present',
                'label' => 'Present',
                'value' => $stats['present'],
                'pct' => $stats['present_pct'],
                'tone' => 'emerald',
                'bar' => 'bg-emerald-500',
                'bg' => 'bg-emerald-50/80 dark:bg-emerald-950/30',
                'ring' => 'ring-emerald-100 dark:ring-emerald-900/40',
                'text' => 'text-emerald-600 dark:text-emerald-400',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'key' => 'late',
                'label' => 'Late',
                'value' => $stats['late'],
                'pct' => $stats['late_pct'],
                'tone' => 'amber',
                'bar' => 'bg-amber-500',
                'bg' => 'bg-amber-50/80 dark:bg-amber-950/30',
                'ring' => 'ring-amber-100 dark:ring-amber-900/40',
                'text' => 'text-amber-600 dark:text-amber-400',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'key' => 'absent',
                'label' => 'Absent',
                'value' => $stats['absent'],
                'pct' => $stats['absent_pct'],
                'tone' => 'red',
                'bar' => 'bg-red-500',
                'bg' => 'bg-red-50/80 dark:bg-red-950/30',
                'ring' => 'ring-red-100 dark:ring-red-900/40',
                'text' => 'text-red-600 dark:text-red-400',
                'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'hint' => $stats['not_recorded'] > 0 ? "{$stats['explicit_absent']} marked · {$stats['not_recorded']} no record" : null,
            ],
            [
                'key' => 'excused',
                'label' => 'Excused',
                'value' => $stats['excused'],
                'pct' => $stats['excused_pct'],
                'tone' => 'blue',
                'bar' => 'bg-blue-500',
                'bg' => 'bg-blue-50/80 dark:bg-blue-950/30',
                'ring' => 'ring-blue-100 dark:ring-blue-900/40',
                'text' => 'text-blue-600 dark:text-blue-400',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
        ];
        $secondaryMetrics = [
            [
                'key' => 'half_day',
                'label' => 'Half day',
                'value' => $stats['half_day'],
                'hint' => $isSingleDay ? 'Marked today' : 'In period',
                'text' => 'text-orange-600',
                'iconBg' => 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
                'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'key' => 'visitors',
                'label' => 'Visitors',
                'value' => $stats['visitors'],
                'hint' => $stats['visitors_inside'] > 0 ? "{$stats['visitors_inside']} still inside" : ($isSingleDay ? 'Checked in' : 'On end date'),
                'text' => 'text-green-700 dark:text-brand-400',
                'iconBg' => 'bg-brand-100 text-green-700 dark:bg-brand-900/30 dark:text-brand-400',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            ],
            [
                'key' => 'checkouts',
                'label' => 'Checkouts',
                'value' => $stats['checkouts'],
                'hint' => $isSingleDay ? 'Gate exits' : 'On end date',
                'text' => 'text-violet-600',
                'iconBg' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
                'icon' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
            ],
            [
                'key' => $isSingleDay ? 'recorded' : 'attended',
                'label' => $isSingleDay ? 'Recorded' : 'Student-days',
                'value' => $isSingleDay ? "{$stats['recorded_today']}/{$stats['total_students']}" : $stats['denominator'],
                'hint' => $isSingleDay
                    ? ($stats['not_recorded'] > 0 ? "{$stats['not_recorded']} pending" : 'All recorded')
                    : 'Weekday scope',
                'text' => 'text-slate-800 dark:text-white',
                'iconBg' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            ],
        ];
    @endphp

    <div class="grid lg:grid-cols-12 gap-4 mb-6">
        {{-- Hero attendance rate --}}
        <div class="lg:col-span-4 panel relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-400 via-emerald-400 to-brand-400"></div>
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Attendance rate</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $isSingleDay ? 'Present + late + excused' : 'Across student-days in period' }}
                    </p>
                </div>
                @if ($isSingleDay && $stats['present_trend'] !== null)
                    <span @class([
                        'inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold',
                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $stats['present_trend'] >= 0,
                        'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' => $stats['present_trend'] < 0,
                    ])>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $stats['present_trend'] >= 0 ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3' }}" />
                        </svg>
                        {{ $stats['present_trend'] >= 0 ? '+' : '' }}{{ $stats['present_trend'] }}%
                    </span>
                @endif
            </div>

            <div class="flex items-end gap-3 mb-5">
                <p @class([
                    'text-5xl font-bold tracking-tight leading-none',
                    'text-teal-600 dark:text-teal-400' => $stats['attendance_percent'] >= 90,
                    'text-amber-600 dark:text-amber-400' => $stats['attendance_percent'] >= 75 && $stats['attendance_percent'] < 90,
                    'text-red-600 dark:text-red-400' => $stats['attendance_percent'] < 75,
                ])>
                    {{ $stats['attendance_percent'] }}<span class="text-3xl">%</span>
                </p>
                <div class="pb-1">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $attendedCount }} attended</p>
                    <p class="text-xs text-slate-400">of {{ $isSingleDay ? $stats['total_students'] : $stats['denominator'] }} {{ $isSingleDay ? 'students' : 'student-days' }}</p>
                </div>
            </div>

            <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden flex mb-3">
                @foreach ($barSegments as $segment)
                    @if ($segment['value'] > 0)
                        <div
                            class="h-full {{ $segment['color'] }} transition-all duration-500 first:rounded-l-full last:rounded-r-full"
                            style="width: {{ round(($segment['value'] / $denominator) * 100, 2) }}%"
                            title="{{ $segment['label'] }}: {{ $segment['value'] }}"
                        ></div>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-wrap gap-x-3 gap-y-1.5 mb-4">
                @foreach ($barSegments as $segment)
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                        <span class="h-2 w-2 rounded-full {{ $segment['color'] }}"></span>
                        <span>{{ $segment['label'] }}</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $segment['value'] }}</span>
                    </div>
                @endforeach
            </div>

            <button
                type="button"
                wire:click="openStatusDetails('attended')"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300"
            >
                View details
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        {{-- Primary status metrics --}}
        <div class="lg:col-span-8 grid grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach ($statusMetrics as $metric)
                <div class="rounded-2xl ring-1 {{ $metric['ring'] }} {{ $metric['bg'] }} p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-9 w-9 rounded-xl bg-white/80 dark:bg-slate-900/50 flex items-center justify-center {{ $metric['text'] }} shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $metric['icon'] }}" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-slate-400">{{ $metric['pct'] }}%</span>
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $metric['label'] }}</p>
                    <p class="text-3xl font-bold mt-0.5 {{ $metric['text'] }}">{{ $metric['value'] }}</p>
                    <div class="mt-auto pt-3">
                        <div class="h-1.5 rounded-full bg-white/70 dark:bg-slate-900/40 overflow-hidden">
                            <div class="h-full rounded-full {{ $metric['bar'] }}" style="width: {{ min(100, $metric['pct']) }}%"></div>
                        </div>
                        @if (! empty($metric['hint']))
                            <p class="text-[10px] text-slate-500 mt-2 leading-tight">{{ $metric['hint'] }}</p>
                        @else
                            <p class="text-[10px] text-slate-400 mt-2">
                                of {{ $isSingleDay ? $stats['total_students'] : $stats['denominator'] }}
                                {{ $isSingleDay ? 'students' : 'student-days' }}
                            </p>
                        @endif
                        <button
                            type="button"
                            wire:click="openStatusDetails('{{ $metric['key'] }}')"
                            class="mt-3 inline-flex items-center gap-1 text-xs font-semibold {{ $metric['text'] }} hover:underline"
                        >
                            View details
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Secondary metrics --}}
    <div class="panel mb-6 py-3 px-3 sm:px-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
            @foreach ($secondaryMetrics as $metric)
                <div class="flex items-center gap-3 rounded-xl px-2.5 py-2 sm:px-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 {{ $metric['iconBg'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $metric['icon'] }}" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $metric['label'] }}</p>
                        <p class="text-xl font-bold leading-tight {{ $metric['text'] }}">{{ $metric['value'] }}</p>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="text-[11px] text-slate-400 truncate">{{ $metric['hint'] }}</p>
                            <button
                                type="button"
                                wire:click="openStatusDetails('{{ $metric['key'] }}')"
                                class="shrink-0 text-[11px] font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                            >
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid lg:grid-cols-12 gap-6 mb-6">
        <div
            class="lg:col-span-5 panel"
            wire:key="trend-{{ $period }}-{{ $date }}-{{ $department }}-{{ $grade }}-{{ $section }}"
            wire:ignore
            x-data="weeklyAttendanceChart(@js($weeklyLabels), @js($weeklyData))"
        >
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">{{ $trendTitle }}</h3>
            <p class="text-xs text-slate-500 mb-4">% of active students marked present, late, or excused</p>
            <div class="relative h-[220px] w-full">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div
            class="lg:col-span-4 panel"
            wire:key="status-{{ $period }}-{{ $date }}-{{ $department }}-{{ $grade }}-{{ $section }}"
            wire:ignore
            x-data="statusBreakdownChart(@js(array_keys($statusBreakdown)), @js(array_values($statusBreakdown)), @js(array_values($statusBreakdownColors)))"
        >
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Status Breakdown</h3>
            <p class="text-xs text-slate-500 mb-4">
                {{ $isSingleDay ? 'Attendance by status (gate + class)' : 'Aggregated status counts for the selected period' }}
            </p>
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
            <div class="space-y-3 flex-1">
                @foreach ([
                    ['label' => 'Currently in Campus', 'value' => $liveOverview['inside_campus'], 'color' => 'text-green-700'],
                    ['label' => 'Classes in Session', 'value' => $liveOverview['classes_in_session'], 'color' => 'text-slate-800 dark:text-white'],
                    ['label' => 'Visitors Inside', 'value' => $liveOverview['visitors_inside'], 'color' => $liveOverview['visitors_inside'] > 0 ? 'text-blue-600' : 'text-slate-400'],
                    ['label' => 'Half-day Today', 'value' => $liveOverview['half_day'], 'color' => $liveOverview['half_day'] > 0 ? 'text-orange-600' : 'text-slate-400'],
                    ['label' => 'Attendance Alerts', 'value' => $liveOverview['active_alerts'], 'color' => $liveOverview['active_alerts'] > 0 ? 'text-amber-600' : 'text-slate-400'],
                    ['label' => 'Pending Enrollments', 'value' => $liveOverview['pending_approvals'], 'color' => $liveOverview['pending_approvals'] > 0 ? 'text-amber-600' : 'text-slate-800 dark:text-white'],
                ] as $item)
                    <div class="flex items-center justify-between py-1.5 border-b border-surface-border dark:border-slate-800 last:border-0">
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $item['label'] }}</span>
                        <span class="text-lg font-bold {{ $item['color'] }}">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Detailed attendance report --}}
    <div class="panel mb-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Detailed Attendance Report</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Daily breakdown for {{ $periodLabel }}
                    @if ($department || $grade || $section)
                        with current scope filters
                    @endif
                </p>
            </div>
            @can('reports.view')
                <a
                    href="{{ route('reports.index', array_filter([
                        'reportType' => 'attendance_summary',
                        'dateFrom' => $rangeStart->toDateString(),
                        'dateTo' => $rangeEnd->toDateString(),
                        'department' => $department ?: null,
                        'grade' => $grade ?: null,
                        'section' => $section ?: null,
                    ])) }}"
                    wire:navigate
                    class="text-sm font-medium text-green-700 hover:text-brand-500"
                >
                    Open full reports →
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full data-table text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Excused</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Not recorded</th>
                        <th class="text-center">Recorded</th>
                        <th class="text-center">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detailedReport as $row)
                        <tr @class(['opacity-60' => $row['is_weekend']])>
                            <td class="font-medium text-slate-800 dark:text-slate-100">
                                {{ $row['label'] }}
                                @if ($row['is_weekend'])
                                    <span class="text-[10px] uppercase text-slate-400 ml-1">Weekend</span>
                                @endif
                            </td>
                            <td class="text-center text-emerald-600 font-semibold">{{ $row['present'] }}</td>
                            <td class="text-center text-amber-600 font-semibold">{{ $row['late'] }}</td>
                            <td class="text-center text-blue-600 font-semibold">{{ $row['excused'] }}</td>
                            <td class="text-center text-red-600 font-semibold">{{ $row['absent'] }}</td>
                            <td class="text-center text-slate-500">{{ $row['not_recorded'] }}</td>
                            <td class="text-center">{{ $row['recorded'] }}/{{ $row['total_students'] }}</td>
                            <td class="text-center">
                                <span @class([
                                    'inline-flex min-w-[3.25rem] justify-center px-2 py-0.5 rounded-full text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $row['attendance_percent'] >= 90,
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $row['attendance_percent'] >= 75 && $row['attendance_percent'] < 90,
                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' => $row['attendance_percent'] < 75,
                                ])>
                                    {{ $row['attendance_percent'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-500">No attendance data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Breakdowns --}}
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Attendance by Section</h3>
            <p class="text-xs text-slate-500 mb-4">Present + late + excused rate for {{ $periodLabel }}</p>
            <div class="space-y-3">
                @forelse ($sectionBreakdown as $row)
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $row['section'] }}</p>
                            <span class="text-sm font-bold text-teal-600 shrink-0">{{ $row['rate'] }}%</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-teal-500" style="width: {{ min(100, $row['rate']) }}%"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $row['students'] }} students · {{ $row['attended'] }}/{{ $row['student_days'] }} attended days</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No section data for the current filters.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">
                {{ $departmentBreakdown !== [] ? 'Attendance by Department' : 'Late Arrivals' }}
            </h3>
            <p class="text-xs text-slate-500 mb-4">
                {{ $departmentBreakdown !== [] ? 'Focus date attendance rate by department' : 'Gate late status on '.$focusDate->format('M j, Y') }}
            </p>

            @if ($departmentBreakdown !== [])
                <div class="space-y-3 mb-5">
                    @foreach ($departmentBreakdown as $row)
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-surface-border dark:border-slate-800 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $row['department'] }}</p>
                                <p class="text-[11px] text-slate-400">{{ $row['attended'] }} of {{ $row['students'] }} attended</p>
                            </div>
                            <span class="text-sm font-bold text-slate-800 dark:text-white shrink-0">{{ $row['rate'] }}%</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="{{ $departmentBreakdown !== [] ? 'pt-2 border-t border-surface-border dark:border-slate-800' : '' }}">
                @if ($departmentBreakdown !== [])
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-3">Late arrivals</p>
                @endif
                <div class="space-y-2.5">
                    @forelse ($lateArrivals as $record)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $record->student?->list_name ?? 'Student' }}</p>
                                <p class="text-[11px] text-slate-400">
                                    {{ $record->student?->gradeLevel?->name ?? '—' }}
                                    @if ($record->student?->section) · {{ $record->student->section->name }} @endif
                                </p>
                            </div>
                            <span class="text-xs font-mono font-semibold text-amber-600 shrink-0">
                                {{ $record->time_in ? \Illuminate\Support\Str::substr((string) $record->time_in, 0, 5) : '—' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No late arrivals on this date.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom row --}}
    <div class="grid lg:grid-cols-4 gap-6">
        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Recent Check-ins</h3>
            <p class="text-xs text-slate-500 mb-4">Latest gate entries on {{ $focusDate->format('M j') }}</p>
            <div class="space-y-3">
                @forelse ($recentCheckIns as $record)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $record->student?->list_name ?? 'Student' }}</p>
                            <p class="text-[11px] text-slate-400 font-mono">{{ $record->student?->student_number ?? '—' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-mono font-semibold text-slate-700 dark:text-slate-200">
                                {{ $record->time_in ? \Illuminate\Support\Str::substr((string) $record->time_in, 0, 5) : '—' }}
                            </p>
                            <p class="text-[10px] uppercase font-semibold
                                @if ($record->status?->value === 'present') text-emerald-600
                                @elseif ($record->status?->value === 'late') text-amber-600
                                @elseif ($record->status?->value === 'excused') text-blue-600
                                @else text-slate-400 @endif">
                                {{ $record->status?->label() ?? '—' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No gate check-ins for this date.</p>
                @endforelse
            </div>
        </div>

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
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Top Absentees</h3>
            <p class="text-xs text-slate-500 mb-4">Weekdays with no attendance or marked absent · {{ $periodLabel }}</p>
            <div class="space-y-3">
                @forelse ($topAbsentees as $index => $student)
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-400 w-4">{{ $index + 1 }}</span>
                        <div class="h-9 w-9 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-green-700 dark:text-brand-300 text-sm font-semibold shrink-0">
                            {{ strtoupper(substr($student->first_name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $student->list_name }}</p>
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
                    <p class="text-sm text-slate-500">No absences recorded for this period — great attendance!</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Class Schedule</h3>
            <p class="text-xs text-slate-500 mb-4">{{ $focusDate->format('l, F j') }}</p>
            <div class="space-y-3">
                @forelse ($todaysSchedules as $schedule)
                    <div class="flex items-start gap-3">
                        <div @class([
                            'flex flex-col items-center justify-center h-12 w-12 rounded-xl shrink-0 border',
                            'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' => $schedule['status'] === 'In session',
                            'bg-brand-50 dark:bg-brand-900/30 border-brand-100 dark:border-brand-800' => in_array($schedule['status'], ['Upcoming', 'Scheduled'], true),
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
                    <p class="text-sm text-slate-500">No classes scheduled for this day.</p>
                    <a href="{{ route('settings.academic.schedules') }}" wire:navigate class="text-sm font-medium text-green-700">Configure schedules</a>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Status details modal --}}
    <div
        x-data="{ show: @entangle('showDetailsModal').live }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-8 sm:px-6"
        @keydown.escape.window="$wire.closeStatusDetails()"
    >
        <div
            x-show="show"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            wire:click="closeStatusDetails"
        ></div>

        <div class="relative flex min-h-full items-center justify-center">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative w-full max-w-3xl panel shadow-2xl p-0 overflow-hidden"
                @click.stop
            >
                <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-surface-border dark:border-slate-800">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">Attendance details</p>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5">
                            {{ $statusDetails['title'] ?? 'Details' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $statusDetails['subtitle'] ?? $periodLabel }}
                            @if ($statusDetails)
                                · {{ count($statusDetails['rows']) }} {{ \Illuminate\Support\Str::plural('record', count($statusDetails['rows'])) }}
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="closeStatusDetails" class="btn-ghost p-2 -mr-2 -mt-1 rounded-lg" aria-label="Close">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($statusDetails)
                    @if (($statusDetails['groups'] ?? []) !== [])
                        <div class="px-5 py-3 flex flex-wrap gap-2 border-b border-surface-border dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40">
                            @foreach ($statusDetails['groups'] as $group)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white dark:bg-slate-800 border border-surface-border dark:border-slate-700 px-2.5 py-1 text-xs">
                                    <span class="text-slate-500">{{ $group['label'] }}</span>
                                    <span class="font-semibold text-slate-800 dark:text-white">{{ $group['count'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="max-h-[60vh] overflow-y-auto">
                        @if (($statusDetails['rows'] ?? []) === [])
                            <div class="px-5 py-14 text-center text-slate-500">
                                <p class="font-medium">No records found</p>
                                <p class="text-sm mt-1">Nothing matches this status for the selected filters.</p>
                            </div>
                        @else
                            <table class="w-full data-table text-sm">
                                <thead class="sticky top-0 bg-white dark:bg-slate-900 z-10">
                                    <tr>
                                        @foreach ($statusDetails['columns'] as $column)
                                            <th @class(['text-left' => $loop->first, 'text-center' => ! $loop->first])>{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statusDetails['rows'] as $row)
                                        <tr wire:key="detail-row-{{ $statusDetails['category'] }}-{{ $row['student_id'] ?? $loop->index }}-{{ $loop->index }}">
                                            <td>
                                                @if ($row['student_id'])
                                                    <a href="{{ route('students.show', $row['student_id']) }}" wire:navigate class="font-medium text-slate-800 dark:text-white hover:text-green-700">
                                                        {{ $row['name'] }}
                                                    </a>
                                                @else
                                                    <p class="font-medium text-slate-800 dark:text-white">{{ $row['name'] }}</p>
                                                @endif
                                                <p class="text-xs text-slate-400 font-mono">{{ $row['student_number'] }}</p>
                                            </td>
                                            <td class="text-center">
                                                <p class="text-slate-700 dark:text-slate-200">{{ $row['grade'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $row['section'] }}</p>
                                            </td>
                                            <td class="text-center">
                                                <span @class([
                                                    'inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase',
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => ($row['status'] ?? null) === 'present',
                                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => ($row['status'] ?? null) === 'late',
                                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' => ($row['status'] ?? null) === 'excused',
                                                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' => ($row['status'] ?? null) === 'half_day',
                                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' => ($row['status'] ?? null) === 'absent',
                                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => ! in_array($row['status'] ?? null, ['present', 'late', 'excused', 'half_day', 'absent'], true),
                                                ])>
                                                    {{ $row['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="text-center font-mono text-xs text-slate-600 dark:text-slate-300">
                                                @if ($statusDetails['is_single_day'])
                                                    {{ $row['time_in'] ?? '—' }}
                                                @else
                                                    {{ $row['days'] ?? 0 }}d
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @else
                    <div class="px-5 py-14 text-center text-slate-500" wire:loading.flex wire:target="openStatusDetails">
                        Loading details…
                    </div>
                @endif

                <div class="px-5 py-3 border-t border-surface-border dark:border-slate-800 flex justify-end">
                    <button type="button" wire:click="closeStatusDetails" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
