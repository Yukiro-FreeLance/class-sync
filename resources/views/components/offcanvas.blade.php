@props([
    'model' => 'quickAddPanel',
    'title' => 'Quick Add',
])

<div
    x-data="{ open: @entangle($model).live }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[70]"
    @keydown.escape.window="$wire.closeQuickAdd()"
>
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"
        wire:click="closeQuickAdd"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-surface-border bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
        @click.stop
    >
        <div class="flex items-start justify-between gap-3 border-b border-surface-border px-5 py-4 dark:border-slate-800">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">Quick Add</p>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
            </div>
            <button type="button" wire:click="closeQuickAdd" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>
    </div>
</div>
