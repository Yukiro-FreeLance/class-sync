@php
    $stepLabels = [
        'key_generate' => 'Generate Application Key',
        'migrate' => 'Run Database Migrations',
        'seed' => 'Seed Database',
        'storage_link' => 'Create Storage Link',
        'optimize' => 'Optimize Application',
        'mark_complete' => 'Mark Installation Complete',
        'admin_account' => 'Create Administrator',
        'school_settings' => 'Configure School Settings',
        'finalize' => 'Finalize Setup',
    ];
@endphp

<div
    x-data="{ started: @js($installing || $completed || $failed || count($logs) > 0) }"
    x-init="
        if (!started) {
            started = true;
            $wire.startInstallation();
        }
    "
>
    {{-- Progress bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-indigo-100">
                @if ($installing)
                    Installing...
                @elseif ($completed)
                    Installation Complete
                @elseif ($failed)
                    Installation Failed
                @else
                    Preparing...
                @endif
            </span>
            <span class="text-sm font-semibold text-indigo-300">{{ $progress }}%</span>
        </div>
        <div class="h-2.5 rounded-full bg-white/10 overflow-hidden">
            <div
                @class([
                    'h-full rounded-full transition-all duration-500 ease-out',
                    'bg-indigo-500' => ! $failed,
                    'bg-red-500' => $failed,
                    'animate-pulse' => $installing,
                ])
                style="width: {{ $installing && $progress === 0 ? '15' : $progress }}%"
            ></div>
        </div>
    </div>

    {{-- Logs --}}
    <div class="rounded-xl bg-black/20 border border-white/10 p-4 mb-6 max-h-72 overflow-y-auto font-mono text-xs space-y-2">
        @forelse ($logs as $index => $log)
            <div
                wire:key="log-{{ $index }}"
                @class([
                    'flex items-start gap-2',
                    'text-emerald-300' => $log['status'] === 'success',
                    'text-red-300' => $log['status'] === 'error',
                ])
            >
                @if ($log['status'] === 'success')
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                @endif
                <div>
                    <span class="font-semibold">{{ $stepLabels[$log['step']] ?? ucfirst(str_replace('_', ' ', $log['step'])) }}:</span>
                    <span class="text-indigo-200/80">{{ $log['message'] }}</span>
                </div>
            </div>
        @empty
            <div class="flex items-center gap-2 text-indigo-300/60">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Starting installation...
            </div>
        @endforelse
    </div>

    @if ($failed)
        <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 mb-6">
            <p class="text-sm text-red-300">{{ $errorMessage ?? 'An error occurred during installation.' }}</p>
        </div>

        <div class="flex justify-end">
            <button
                type="button"
                wire:click="retry"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all duration-200"
            >
                <svg wire:loading wire:target="retry" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Retry Installation
            </button>
        </div>
    @endif
</div>
