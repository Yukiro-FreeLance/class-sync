@props([
    'colspan',
    'genderKey',
    'count',
    'groups',
])

@if (\App\Services\Students\StudentListService::showGenderHeader($genderKey, $groups))
    <tr {{ $attributes->merge(['class' => 'student-list-gender-row']) }}>
        <td colspan="{{ $colspan }}"
            class="py-2 px-3 text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 border-b border-surface-border dark:border-slate-700">
            {{ \App\Services\Students\StudentListService::genderGroupLabel($genderKey) }} ({{ $count }})
        </td>
    </tr>
@endif
