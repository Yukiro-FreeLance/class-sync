<div>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 mb-6">
            <x-application-logo class="w-12 h-12 fill-current text-indigo-300" />
        </div>

        <h3 class="text-xl font-semibold text-white">Welcome to Class Sync</h3>
        <p class="mt-2 text-sm text-indigo-200/80 max-w-md mx-auto">
            This wizard will guide you through installing and configuring your Student Monitoring System.
        </p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <div class="rounded-xl bg-white/5 border border-white/10 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-indigo-300/70">Application Version</p>
            <p class="mt-1 text-lg font-semibold text-white">{{ $this->appVersion }}</p>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-4">
            <p class="text-xs font-medium uppercase tracking-wider text-indigo-300/70">Laravel / PHP</p>
            <p class="mt-1 text-lg font-semibold text-white">{{ $this->laravelVersion }} / {{ $this->phpVersion }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white/5 border border-white/10 p-5 mb-8">
        <h4 class="text-sm font-semibold text-white mb-4">System Requirements</h4>
        <ul class="space-y-3">
            @foreach ($this->requirements as $requirement)
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center">
                        <svg class="w-3 h-3 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-white">{{ $requirement['label'] }}</p>
                        <p class="text-xs text-indigo-200/60">{{ $requirement['value'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="flex justify-end">
        <button
            type="button"
            wire:click="continue"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-transparent"
        >
            Get Started
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </button>
    </div>
</div>
