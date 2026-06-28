@props(['status', 'archived' => false])

@if ($archived)
    <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
        <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
        Archived
    </span>
@else
    @php
        $value = $status?->value ?? '';
        $classes = match ($value) {
            'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'inactive' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
            'graduated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'transferred' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
            'suspended' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        };
        $dot = match ($value) {
            'active' => 'bg-emerald-500',
            'inactive' => 'bg-slate-400',
            'graduated' => 'bg-blue-500',
            'transferred' => 'bg-violet-500',
            'suspended' => 'bg-red-500',
            default => 'bg-slate-400',
        };
    @endphp
    <span @class(['badge', $classes])>
        <span @class(['mr-1 inline-block h-1.5 w-1.5 rounded-full', $dot])></span>
        {{ $status?->label() ?? '—' }}
    </span>
@endif
