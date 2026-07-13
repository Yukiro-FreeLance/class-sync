@props([
    'genderKey',
    'count',
    'groups',
])

@if (\App\Services\Students\StudentListService::showGenderHeader($genderKey, $groups))
    <div {{ $attributes->merge(['class' => 'px-4 sm:px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 border-y border-surface-border dark:border-slate-800']) }}>
        {{ \App\Services\Students\StudentListService::genderGroupLabel($genderKey) }} ({{ $count }})
    </div>
@endif
