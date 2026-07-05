@props([
    'label',
    'panel',
    'optional' => false,
    'error' => null,
])

<div>
    <label class="text-xs font-medium text-slate-500 mb-1 block">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-slate-400">(optional)</span>
        @endif
    </label>
    <div class="flex gap-2">
        <div class="flex-1 min-w-0">
            {{ $slot }}
        </div>
        <button
            type="button"
            wire:click="openQuickAdd('{{ $panel }}')"
            class="btn-secondary shrink-0 px-3"
            title="Add {{ strtolower($label) }}"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>
    @if ($error)
        <x-input-error :messages="$error" class="mt-1" />
    @endif
</div>
