@props(['label', 'value' => null])

<div {{ $attributes->merge(['class' => 'flex gap-3']) }}>
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">
        {{ $icon ?? '' }}
    </div>
    <div class="min-w-0">
        <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</dt>
        <dd class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 break-words">
            @if (isset($slot) && ! $slot->isEmpty())
                {{ $slot }}
            @else
                {{ filled($value) ? $value : '—' }}
            @endif
        </dd>
    </div>
</div>
