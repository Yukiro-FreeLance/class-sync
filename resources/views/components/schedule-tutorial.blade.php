<template x-teleport="body">
    <div
        x-show="active"
        x-cloak
        class="fixed inset-0 z-[90]"
        role="dialog"
        aria-modal="true"
        :aria-label="step.title"
    >
        {{-- Backdrop --}}
        <div
            x-show="!isCentered"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]"
            @click="skip()"
        ></div>

        {{-- Spotlight --}}
        <div
            x-show="!isCentered"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="pointer-events-none fixed z-[91] rounded-2xl ring-4 ring-brand-400/80 ring-offset-2 ring-offset-transparent transition-all duration-300"
            :style="`top:${highlight.top}px;left:${highlight.left}px;width:${highlight.width}px;height:${highlight.height}px;box-shadow:0 0 0 9999px rgba(15,23,42,0.62)`"
        ></div>

        {{-- Centered welcome / finish card --}}
        <div
            x-show="isCentered"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="fixed inset-0 z-[92] flex items-center justify-center p-4 bg-slate-900/55 backdrop-blur-[1px]"
        >
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-surface-border dark:border-slate-800 overflow-hidden" @click.stop>
                <div class="h-1.5 bg-brand-100 dark:bg-brand-900/40">
                    <div class="h-full bg-brand-600 transition-all duration-300" :style="`width:${progress}%`"></div>
                </div>
                <div class="p-6">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300 mb-4">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">
                        Step <span x-text="stepIndex + 1"></span> of <span x-text="steps.length"></span>
                    </p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1" x-text="step.title"></h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-3 leading-relaxed" x-text="step.body"></p>
                    <div class="flex items-center justify-between gap-3 mt-6">
                        <button type="button" @click="skip()" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Skip tour</button>
                        <div class="flex gap-2">
                            <button type="button" x-show="!isFirst" @click="prev()" class="btn-secondary text-sm">Back</button>
                            <button type="button" @click="next()" class="btn-primary text-sm" x-text="isLast ? 'Get started' : 'Next'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Anchored popover --}}
        <div
            x-show="!isCentered"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed z-[92] rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-surface-border dark:border-slate-800 overflow-hidden"
            :style="`top:${popover.top}px;left:${popover.left}px;width:${popover.width}px`"
            @click.stop
        >
            <div class="h-1.5 bg-brand-100 dark:bg-brand-900/40">
                <div class="h-full bg-brand-600 transition-all duration-300" :style="`width:${progress}%`"></div>
            </div>
            <div class="p-5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">
                    Step <span x-text="stepIndex + 1"></span> of <span x-text="steps.length"></span>
                </p>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1" x-text="step.title"></h3>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-2 leading-relaxed" x-text="step.body"></p>
                <div class="flex items-center justify-between gap-3 mt-5 pt-4 border-t border-surface-border dark:border-slate-800">
                    <button type="button" @click="skip()" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Skip tour</button>
                    <div class="flex gap-2">
                        <button type="button" x-show="!isFirst" @click="prev()" class="btn-secondary text-sm px-3 py-2">Back</button>
                        <button type="button" @click="next()" class="btn-primary text-sm px-4 py-2" x-text="isLast ? 'Done' : 'Next'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
