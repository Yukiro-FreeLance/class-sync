@props([
    'title',
    'subtitle' => null,
    'school' => [],
    'academicYear' => null,
])

<div {{ $attributes->merge(['class' => 'student-list-print-header text-center mb-6']) }}>
    <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">{{ $school['school_name'] ?? config('app.name') }}</p>
    @if (! empty($school['school_address']))
        <p class="text-xs text-slate-500 mt-0.5">{{ $school['school_address'] }}</p>
    @endif
    <h2 class="text-lg font-bold text-slate-900 mt-3">{{ $title }}</h2>
    @if ($subtitle)
        <p class="text-sm text-slate-600 mt-1">{{ $subtitle }}</p>
    @endif
    @if ($academicYear)
        <p class="text-xs text-slate-500 mt-1">School Year: {{ $academicYear->name }}</p>
    @endif
</div>
