<div class="w-full max-w-4xl">
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 shadow-lg mb-4">
            <x-application-logo class="w-10 h-10 fill-current text-indigo-300" />
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Student Monitoring System</h1>
        <p class="mt-2 text-sm text-indigo-200/80">Setup Wizard</p>
    </div>

    {{-- Step indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-between gap-1 sm:gap-2">
            @foreach ($steps as $number => $step)
                <button
                    type="button"
                    wire:click="$dispatch('wizard-go-to', { step: {{ $number }} })"
                    @disabled($number >= $currentStep || $number >= 6)
                    class="group flex flex-col items-center flex-1 min-w-0 disabled:cursor-default"
                >
                    <div @class([
                        'flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full text-xs sm:text-sm font-semibold transition-all duration-300 shadow-md',
                        'bg-indigo-500 text-white ring-2 ring-indigo-300/50 scale-110' => $number === $currentStep,
                        'bg-emerald-500/90 text-white' => $number < $currentStep,
                        'bg-white/10 text-indigo-200/70 border border-white/20' => $number > $currentStep,
                    ])>
                        @if ($number < $currentStep)
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $number }}
                        @endif
                    </div>
                    <span @class([
                        'mt-2 text-[10px] sm:text-xs font-medium truncate w-full text-center hidden sm:block',
                        'text-white' => $number === $currentStep,
                        'text-emerald-300/90' => $number < $currentStep,
                        'text-indigo-200/50' => $number > $currentStep,
                    ])>
                        {{ $step['title'] }}
                    </span>
                </button>

                @if (! $loop->last)
                    <div @class([
                        'h-0.5 flex-1 rounded-full transition-colors duration-300 min-w-[8px]',
                        'bg-emerald-500/60' => $number < $currentStep,
                        'bg-white/10' => $number >= $currentStep,
                    ])></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Glass card --}}
    <div class="rounded-2xl bg-white/10 dark:bg-gray-900/40 backdrop-blur-xl border border-white/20 shadow-2xl shadow-indigo-900/20 overflow-hidden">
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-white/10">
            <h2 class="text-lg font-semibold text-white">
                Step {{ $currentStep }}: {{ $steps[$currentStep]['title'] }}
            </h2>
            <p class="mt-1 text-sm text-indigo-200/70">{{ $steps[$currentStep]['description'] }}</p>
        </div>

        <div class="px-6 py-6 sm:px-8 sm:py-8">
            @switch($currentStep)
                @case(1)
                    <livewire:setup.steps.welcome :key="'step-1'" />
                    @break
                @case(2)
                    <livewire:setup.steps.system-check :key="'step-2'" />
                    @break
                @case(3)
                    <livewire:setup.steps.database-config :key="'step-3'" />
                    @break
                @case(4)
                    <livewire:setup.steps.application-config :key="'step-4'" />
                    @break
                @case(5)
                    <livewire:setup.steps.admin-account :key="'step-5'" />
                    @break
                @case(6)
                    <livewire:setup.steps.run-installation :key="'step-6'" />
                    @break
                @case(7)
                    <livewire:setup.steps.finish :key="'step-7'" />
                    @break
            @endswitch
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-indigo-300/50">
        &copy; {{ date('Y') }} Class Sync &mdash; Student Monitoring System
    </p>
</div>
