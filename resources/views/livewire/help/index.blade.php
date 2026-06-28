<div x-data="{ openFaq: null }">
    <x-page-header title="Help Center" subtitle="User manual and frequently asked questions">
        <x-slot name="actions">
            <div class="relative w-full sm:w-72">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search manual or FAQ..."
                    class="input-field pl-10 w-full">
            </div>
        </x-slot>
    </x-page-header>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6">
        <button type="button" wire:click="setTab('manual')" @class([
            'px-4 py-2 rounded-xl text-sm font-medium transition',
            'bg-green-700 text-white shadow-sm' => $activeTab === 'manual',
            'btn-secondary' => $activeTab !== 'manual',
        ])>
            User Manual
        </button>
        <button type="button" wire:click="setTab('faq')" @class([
            'px-4 py-2 rounded-xl text-sm font-medium transition',
            'bg-green-700 text-white shadow-sm' => $activeTab === 'faq',
            'btn-secondary' => $activeTab !== 'faq',
        ])>
            FAQ
        </button>
    </div>

    @if ($activeTab === 'manual')
        <div class="grid lg:grid-cols-[280px_1fr] gap-6 items-start">
            {{-- Section nav --}}
            <aside class="panel p-3 lg:sticky lg:top-24 space-y-1 max-h-[calc(100vh-8rem)] overflow-y-auto">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 px-3 py-2">Topics</p>
                @forelse ($manualSections as $section)
                    <button type="button" wire:click="selectSection('{{ $section['id'] }}')"
                        @class([
                            'w-full flex items-start gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition',
                            'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-200 font-medium' => ($activeSectionData['id'] ?? null) === $section['id'],
                            'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60' => ($activeSectionData['id'] ?? null) !== $section['id'],
                        ])>
                        <svg class="h-5 w-5 shrink-0 mt-0.5 opacity-70" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="{{ $section['icon'] }}" />
                        </svg>
                        <span>
                            <span class="block font-medium">{{ $section['title'] }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ $section['summary'] }}</span>
                        </span>
                    </button>
                @empty
                    <p class="px-3 py-4 text-sm text-slate-500">No topics match your search.</p>
                @endforelse
            </aside>

            {{-- Content --}}
            <div class="space-y-6 min-w-0">
                @if ($activeSectionData)
                    <div class="panel">
                        <div class="flex items-start gap-4 mb-6">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="{{ $activeSectionData['icon'] }}" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                                    {{ $activeSectionData['title'] }}</h2>
                                <p class="text-sm text-slate-500 mt-1">{{ $activeSectionData['summary'] }}</p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            @foreach ($activeSectionData['sections'] as $block)
                                <section>
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-3">
                                        {{ $block['title'] }}</h3>
                                    <ol class="space-y-2.5">
                                        @foreach ($block['steps'] as $index => $step)
                                            <li class="flex gap-3 text-sm text-slate-600 dark:text-slate-300">
                                                <span
                                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $index + 1 }}</span>
                                                <span class="pt-0.5 leading-relaxed">{{ $step }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                    @if (! empty($block['tips']))
                                        <div
                                            class="mt-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200 mb-2">
                                                Tips</p>
                                            <ul class="space-y-1.5">
                                                @foreach ($block['tips'] as $tip)
                                                    <li class="text-sm text-amber-900 dark:text-amber-100 flex gap-2">
                                                        <span class="text-amber-600 dark:text-amber-400">•</span>
                                                        {{ $tip }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="panel text-center py-12">
                        <p class="text-slate-500">No manual topics match your search. Try different keywords or switch to
                            the FAQ tab.</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- FAQ --}}
        <div class="max-w-3xl space-y-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="$set('faqCategory', 'all')" @class([
                    'px-3 py-1.5 rounded-lg text-xs font-medium transition',
                    'bg-green-700 text-white' => $faqCategory === 'all',
                    'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' => $faqCategory !== 'all',
                ])>All</button>
                @foreach ($faqCategories as $category)
                    <button type="button" wire:click="$set('faqCategory', '{{ $category }}')" @class([
                        'px-3 py-1.5 rounded-lg text-xs font-medium transition',
                        'bg-green-700 text-white' => $faqCategory === $category,
                        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' => $faqCategory !== $category,
                    ])>{{ $category }}</button>
                @endforeach
            </div>

            @forelse ($faqItems as $item)
                <div class="panel overflow-hidden p-0">
                    <button type="button" @click="openFaq = openFaq === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}'"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                        <div class="min-w-0">
                            <span
                                class="inline-block text-[10px] font-semibold uppercase tracking-wide text-green-700 dark:text-green-400 mb-1">{{ $item['category'] }}</span>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $item['question'] }}</p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform"
                            :class="openFaq === '{{ $item['id'] }}' ? 'rotate-180' : ''" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openFaq === '{{ $item['id'] }}'" x-transition x-cloak>
                        <div class="px-5 pb-4 pt-0 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-surface-border dark:border-slate-800">
                            <p class="pt-4">{{ $item['answer'] }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="panel text-center py-12">
                    <p class="text-slate-500">No FAQ items match your search or filter.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
