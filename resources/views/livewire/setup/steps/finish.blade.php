<div class="text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/20 border border-emerald-400/30 mb-6">
        <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h3 class="text-2xl font-bold text-white">Installation Complete!</h3>
    <p class="mt-3 text-sm text-indigo-200/80 max-w-md mx-auto">
        <strong class="text-white">{{ $appName }}</strong> has been successfully installed and configured.
        Your administrator account is ready to use.
    </p>

    <div class="mt-8 grid sm:grid-cols-3 gap-4 text-left max-w-lg mx-auto">
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
            <svg class="w-6 h-6 text-indigo-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <p class="text-xs font-medium text-white">Database</p>
            <p class="text-[10px] text-emerald-300 mt-0.5">Configured</p>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
            <svg class="w-6 h-6 text-indigo-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <p class="text-xs font-medium text-white">Admin</p>
            <p class="text-[10px] text-emerald-300 mt-0.5">Created</p>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
            <svg class="w-6 h-6 text-indigo-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-xs font-medium text-white">School</p>
            <p class="text-[10px] text-emerald-300 mt-0.5">Configured</p>
        </div>
    </div>

    <div class="mt-10">
        <button
            type="button"
            wire:click="goToDashboard"
            class="inline-flex items-center gap-2 px-8 py-3 bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-transparent"
        >
            Go to Dashboard
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </button>
    </div>
</div>
