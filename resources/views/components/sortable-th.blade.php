@props([
    'field',
    'label',
    'sort',
    'direction',
    'align' => 'left',
])

<th @class([
    'text-right' => $align === 'right',
    'text-center' => $align === 'center',
])>
    <button type="button" wire:click="sortBy('{{ $field }}')"
        class="inline-flex items-center gap-1 uppercase tracking-wide text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-green-700 dark:hover:text-green-300 transition {{ $align === 'right' ? 'ml-auto' : '' }}">
        <span>{{ $label }}</span>
        @if ($sort === $field)
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if ($direction === 'asc')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                @endif
            </svg>
        @endif
    </button>
</th>
