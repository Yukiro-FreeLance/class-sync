@php
    $statusColors = [
        'pass' => ['bg' => 'bg-emerald-500/20', 'border' => 'border-emerald-500/30', 'dot' => 'bg-emerald-400', 'text' => 'text-emerald-300'],
        'warning' => ['bg' => 'bg-amber-500/20', 'border' => 'border-amber-500/30', 'dot' => 'bg-amber-400', 'text' => 'text-amber-300'],
        'fail' => ['bg' => 'bg-red-500/20', 'border' => 'border-red-500/30', 'dot' => 'bg-red-400', 'text' => 'text-red-300'],
    ];

    $checkLabels = [
        'php_version' => 'PHP Version',
        'extensions' => 'PHP Extensions',
        'storage_writable' => 'Storage Writable',
        'bootstrap_cache_writable' => 'Bootstrap Cache Writable',
        'database_drivers' => 'Database Drivers',
    ];
@endphp

<div>
    @if (! $ran)
        <div class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="ml-3 text-sm text-indigo-200">Running system checks...</span>
        </div>
    @else
        <div class="space-y-3 mb-8">
            @foreach ($checks as $key => $check)
                @php $colors = $statusColors[$check['status']] ?? $statusColors['fail']; @endphp
                <div class="rounded-xl {{ $colors['bg'] }} border {{ $colors['border'] }} p-4">
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 flex-shrink-0 w-2.5 h-2.5 rounded-full {{ $colors['dot'] }}"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white">{{ $checkLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</p>
                            <p class="mt-0.5 text-xs {{ $colors['text'] }}">{{ $check['message'] }}</p>

                            @if (! empty($check['details']))
                                <ul class="mt-3 space-y-1.5 pl-1">
                                    @foreach ($check['details'] as $detailKey => $detail)
                                        @php $detailColors = $statusColors[$detail['status']] ?? $statusColors['fail']; @endphp
                                        <li class="flex items-center gap-2 text-xs {{ $detailColors['text'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $detailColors['dot'] }}"></span>
                                            {{ $detail['message'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! $this->canContinue)
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 mb-6">
                <p class="text-sm text-red-300">Please resolve the failed checks above before continuing.</p>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <button
                type="button"
                wire:click="runChecks"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-colors"
            >
                <svg wire:loading wire:target="runChecks" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Re-run Checks
            </button>

            <button
                type="button"
                wire:click="continue"
                @disabled(! $this->canContinue)
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-500 hover:bg-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all duration-200"
            >
                Continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </div>
    @endif
</div>
