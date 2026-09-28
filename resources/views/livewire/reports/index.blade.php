<div>
    <x-page-header title="Reports" subtitle="Generate, preview, and export attendance reports" />

    <div class="grid xl:grid-cols-[320px_1fr] gap-5 items-start">
        {{-- Filters --}}
        <aside class="xl:sticky xl:top-24 space-y-4">
            <div class="panel space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Report settings</h3>

                <div>
                    <x-input-label value="Report Type" />
                    <select wire:model.live="reportType" class="mt-1 select-field">
                        @foreach ($reportTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if (! in_array($reportType, ['student_list', 'enrollment']))
                    <div>
                        <x-input-label value="Period" />
                        <div class="mt-1 grid grid-cols-2 gap-1 rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                            <button type="button" wire:click="$set('periodMode', 'range')"
                                @class([
                                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                    'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' => $periodMode !== 'month',
                                    'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' => $periodMode === 'month',
                                ])>
                                Date range
                            </button>
                            <button type="button" wire:click="$set('periodMode', 'month')"
                                @class([
                                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                    'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' => $periodMode === 'month',
                                    'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' => $periodMode !== 'month',
                                ])>
                                Month
                            </button>
                        </div>
                    </div>

                    @if ($periodMode === 'month')
                        <div>
                            <x-input-label value="Month" />
                            <x-text-input wire:model.live="month" type="month" class="mt-1 block w-full" />
                            <p class="text-[11px] text-slate-500 mt-1">
                                {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M j, Y') }}
                                –
                                {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M j, Y') }}
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <x-input-label value="Date From" />
                                <x-text-input wire:model.live="dateFrom" type="date" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label value="Date To" />
                                <x-text-input wire:model.live="dateTo" type="date" class="mt-1 block w-full" />
                            </div>
                        </div>
                    @endif

                    @if ($reportType === 'research_attendance')
                        <div>
                            <x-input-label value="Study context" />
                            <textarea wire:model.live.debounce.400ms="studyContext" rows="3" maxlength="180"
                                class="mt-1 input-field"
                                placeholder="e.g. before the implementation of the LAKBAY-GABAY Program"></textarea>
                            <p class="text-[11px] text-slate-500 mt-1">Optional phrase written into the research narrative. Leave blank to use the selected dates.</p>
                        </div>
                    @endif
                @else
                    <p class="text-xs text-slate-500 rounded-lg bg-slate-50 dark:bg-slate-800/50 px-3 py-2">
                        @if ($reportType === 'enrollment')
                            Enrollment report counts enrolled students for the current school year, broken down by year level and section using the scope filters below.
                        @else
                            Student list shows active students using the scope filters below.
                        @endif
                    </p>
                @endif

                <div class="pt-2 border-t border-surface-border dark:border-slate-800 space-y-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Scope filters</p>
                    <select wire:model.live="department" class="select-field">
                        <option value="">All departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="grade" class="select-field" {{ $department ? '' : 'disabled' }}>
                        <option value="">All grades</option>
                        @foreach ($grades as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="section" class="select-field" {{ $grade ? '' : 'disabled' }}>
                        <option value="">All sections</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($canExport && $preview && ! $previewError)
                    <div class="pt-3 border-t border-surface-border dark:border-slate-800 flex flex-col gap-2">
                        <a href="{{ $this->exportUrl('xlsx') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                            Export Excel
                        </a>
                        <a href="{{ $this->exportUrl('csv') }}" class="btn-secondary w-full justify-center">
                            Export CSV
                        </a>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Preview --}}
        <div class="space-y-4">
            @if ($previewError)
                <div class="panel border-red-200 bg-red-50 dark:bg-red-900/10 text-sm text-red-700 dark:text-red-300">
                    {{ $previewError }}
                </div>
            @elseif ($preview)
                <div class="panel">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-5 pb-4 border-b border-surface-border dark:border-slate-800">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600">{{ $schoolName }}</p>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $preview->title }}</h2>
                            <p class="text-sm text-slate-500 mt-1">{{ $preview->periodLabel }}</p>
                        </div>
                        <p class="text-xs text-slate-400">Generated {{ now()->format('M j, Y g:i A') }}</p>
                    </div>

                    @if ($preview->summaryStats !== [])
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                            @foreach ($preview->summaryStats as $stat)
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 px-3 py-3 text-center">
                                    <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $stat['value'] }}</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-tight">{{ $stat['label'] }}</p>
                                    @if (! empty($stat['hint']))
                                        <p class="text-[10px] text-slate-400 mt-1">{{ $stat['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($preview->narrative)
                        <div class="mb-6 rounded-xl border border-brand-100 bg-brand-50/70 dark:border-brand-900/40 dark:bg-brand-950/20 px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 mb-2">Research narrative</p>
                            <p class="text-sm leading-relaxed text-slate-800 dark:text-slate-200">{{ $preview->narrative }}</p>
                            @if ($preview->narrativeNote)
                                <p class="text-xs text-slate-500 mt-3">{{ $preview->narrativeNote }}</p>
                            @endif
                        </div>
                    @endif

                    @if ($preview->charts !== [])
                        <div class="grid lg:grid-cols-2 gap-4 mb-6">
                            @foreach ($preview->charts as $chart)
                                <div
                                    @class(['rounded-xl border border-surface-border dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40 p-4', 'lg:col-span-2' => ! empty($chart['wide'])])
                                    wire:key="report-chart-{{ $reportType }}-{{ $dateFrom }}-{{ $dateTo }}-{{ $department }}-{{ $grade }}-{{ $section }}-{{ $chart['key'] }}"
                                    wire:ignore
                                    x-data="reportBarChart(@js($chart['labels']), @js($chart['datasets']), @js($chart['yMax'] ?? null), @js($chart['suffix'] ?? ''))"
                                >
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $chart['title'] }}</h3>
                                    @if (! empty($chart['subtitle']))
                                        <p class="text-xs text-slate-500 mt-0.5 mb-3">{{ $chart['subtitle'] }}</p>
                                    @endif
                                    <div class="relative h-[240px] w-full">
                                        <canvas x-ref="canvas"></canvas>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($preview->narrative)
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mb-6 text-[11px] text-slate-500">
                                @foreach ([
                                    'Very High' => '#059669',
                                    'High' => '#10b981',
                                    'Average' => '#d97706',
                                    'Low' => '#ea580c',
                                    'Very Low' => '#dc2626',
                                ] as $label => $color)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $color }}"></span>
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if ($displayRows === [] && $preview->tables === [] && $preview->charts === [] && ! $preview->narrative)
                        <div class="text-center py-16 text-slate-500">
                            <p class="font-medium">No records found</p>
                            <p class="text-sm mt-1">Try adjusting the date range or scope filters.</p>
                        </div>
                    @else
                        @if ($preview->narrative)
                            @foreach ($preview->tables as $table)
                                <div class="mb-6">
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-2">{{ $table['title'] }}</h3>
                                    <div class="overflow-x-auto">
                                        <table class="w-full data-table text-sm">
                                            <thead>
                                                <tr>
                                                    @foreach ($table['columns'] as $column)
                                                        <th @class(['text-center' => ($column['align'] ?? '') === 'center'])>{{ $column['label'] }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($table['rows'] as $row)
                                                    <tr>
                                                        @foreach ($table['columns'] as $column)
                                                            <td @class(['text-center' => ($column['align'] ?? '') === 'center'])>{{ $row[$column['key']] ?? '—' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if ($displayRows !== [])
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $preview->rowsTitle ?? 'Detail' }}</h3>
                                @if ($truncated)
                                    <span class="text-xs text-amber-600 dark:text-amber-400">Showing first 100 of {{ $preview->totalRows }} rows — export for full data</span>
                                @else
                                    <span class="text-xs text-slate-400">{{ $preview->totalRows }} row(s)</span>
                                @endif
                            </div>
                            <div class="overflow-x-auto mb-6">
                                <table class="w-full data-table text-sm">
                                    <thead>
                                        <tr>
                                            @foreach ($preview->columns as $column)
                                                <th @class(['text-right' => ($column['align'] ?? '') === 'center'])>{{ $column['label'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($displayRows as $row)
                                            <tr>
                                                @foreach ($preview->columns as $column)
                                                    <td @class([
                                                        'text-center' => ($column['align'] ?? '') === 'center',
                                                        'font-mono text-xs' => $column['key'] === 'student_number',
                                                    ])>{{ $row[$column['key']] ?? '—' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if (! $preview->narrative)
                        @foreach ($preview->tables as $table)
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-2">{{ $table['title'] }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full data-table text-sm">
                                        <thead>
                                            <tr>
                                                @foreach ($table['columns'] as $column)
                                                    <th @class(['text-center' => ($column['align'] ?? '') === 'center'])>{{ $column['label'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($table['rows'] as $row)
                                                <tr>
                                                    @foreach ($table['columns'] as $column)
                                                        <td @class(['text-center' => ($column['align'] ?? '') === 'center'])>{{ $row[$column['key']] ?? '—' }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    @endif
                </div>
            @else
                <div class="panel text-center py-20 text-slate-500">
                    <p>Select report options to generate a preview.</p>
                </div>
            @endif
        </div>
    </div>
</div>
