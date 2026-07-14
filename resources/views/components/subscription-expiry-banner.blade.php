@props([
    'banner',
])

@if ($banner)
    <div
        x-data="subscriptionExpiryBanner(@js($banner['storage_key']))"
        x-show="visible"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="relative z-30 border-b border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
        role="status"
        aria-live="polite"
    >
        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
                <div
                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="min-w-0 text-sm leading-relaxed">
                    <p class="font-semibold">
                        Hosting will end on {{ $banner['expires_at_label'] }}.
                    </p>
                    <p class="mt-0.5 text-amber-800/90 dark:text-amber-200/90">
                        Please update your system admin about possible downtime.
                        @if ($banner['days_remaining'] === 0)
                            <span class="font-medium">(Ends today)</span>
                        @elseif ($banner['days_remaining'] === 1)
                            <span class="font-medium">(1 day remaining)</span>
                        @else
                            <span class="font-medium">({{ $banner['days_remaining'] }} days remaining)</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                <button type="button" @click="remindLater()"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 shadow-sm hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-950/60 dark:text-amber-100 dark:hover:bg-amber-900/40">
                    Remind me later
                </button>
                <button type="button" @click="acknowledge()"
                    class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-500 dark:bg-amber-500 dark:hover:bg-amber-400">
                    I understand
                </button>
                <button type="button" @click="remindLater()"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-amber-700 hover:bg-amber-100 dark:text-amber-200 dark:hover:bg-amber-900/50"
                    aria-label="Dismiss banner">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endif
