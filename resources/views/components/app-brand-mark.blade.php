@props([
    'logoUrl' => null,
    'acronym' => 'CS',
    'schoolName' => config('app.name'),
    'size' => 'sm',
    'framed' => false,
])

@php
    $markSize = match ($size) {
        'md' => 'h-11 w-11',
        'lg' => 'h-14 w-14',
        default => 'h-10 w-10',
    };
    $textSize = match ($size) {
        'md' => 'text-sm',
        'lg' => 'text-base',
        default => 'text-xs',
    };
@endphp

<div {{ $attributes->class([
    'shrink-0',
    $framed ? 'rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-sm ring-1 ring-slate-200/80 dark:ring-slate-700' : null,
]) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" @class([
            'object-contain',
            $markSize,
            $framed ? 'rounded-xl' : 'rounded-xl shrink-0',
        ])>
    @else
        <div @class([
            'flex items-center justify-center rounded-xl bg-gradient-to-br from-green-700 to-green-800 text-white font-bold shadow-md shadow-brand-500/25',
            $markSize,
            $textSize,
        ])>
            {{ $acronym }}
        </div>
    @endif
</div>
