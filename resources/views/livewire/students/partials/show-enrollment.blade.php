<section class="panel">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Enrollment</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $student->academicYear?->name ?? 'No academic year set' }}</p>
        </div>
        @can('enroll', $student)
            <a href="{{ route('students.enroll', $student) }}" wire:navigate class="btn-secondary text-sm">Update Enrollment</a>
        @endcan
    </div>

    @if ($currentEnrollment)
        <dl class="grid sm:grid-cols-2 gap-6 mb-8">
            <x-student-profile-field label="Grade Level" :value="$currentEnrollment->gradeLevel?->name">
                <x-slot:icon>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" /></svg>
                </x-slot:icon>
            </x-student-profile-field>
            <x-student-profile-field label="Section" :value="$currentEnrollment->section?->name">
                <x-slot:icon>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                </x-slot:icon>
            </x-student-profile-field>
            <x-student-profile-field label="Track / Strand" :value="$currentEnrollment->course?->name">
                <x-slot:icon>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </x-slot:icon>
            </x-student-profile-field>
            <x-student-profile-field label="Status" :value="$currentEnrollment->status?->label()">
                <x-slot:icon>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </x-slot:icon>
            </x-student-profile-field>
            <x-student-profile-field label="Enrollment Date" :value="$currentEnrollment->enrollment_date?->format('M d, Y')">
                <x-slot:icon>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                </x-slot:icon>
            </x-student-profile-field>
            @if ($currentEnrollment->remarks)
                <x-student-profile-field label="Remarks" :value="$currentEnrollment->remarks" class="sm:col-span-2">
                    <x-slot:icon>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                    </x-slot:icon>
                </x-student-profile-field>
            @endif
        </dl>

        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Assigned Classes</h4>
        @if ($currentEnrollment->classSchedules->isNotEmpty())
            <div class="space-y-2">
                @foreach ($currentEnrollment->classSchedules->groupBy(fn ($class) => $class->semester->value) as $semester => $classes)
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mt-4 first:mt-0">
                        {{ $student->gradeLevel?->department?->labelForSemester($semester) ?? \App\Enums\Semester::from($semester)->label() }}
                    </p>
                    @foreach ($classes as $class)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 border border-surface-border dark:border-slate-800">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $class->subject?->name ?? 'Subject' }}</p>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ $class->day_of_week?->label() }}
                                {{ substr((string) $class->starts_at, 0, 5) }}–{{ substr((string) $class->ends_at, 0, 5) }}
                                @if ($class->teacher)
                                    &middot; {{ $class->teacher->name }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500">No classes assigned yet.</p>
        @endif
    @else
        <div class="text-center py-10">
            <p class="text-sm text-slate-500 mb-4">This student has no enrollment record for the current academic year.</p>
            @can('enroll', $student)
                <a href="{{ route('students.enroll', $student) }}" wire:navigate class="btn-primary text-sm">Enroll for this year</a>
            @endcan
        </div>
    @endif

    @if ($enrollments->count() > 1 || ($enrollments->count() === 1 && ! $currentEnrollment))
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mt-10 mb-4 pt-6 border-t border-surface-border dark:border-slate-800">Enrollment History</h3>
        <div class="space-y-3">
            @foreach ($enrollments as $enrollment)
                @continue($currentEnrollment && $enrollment->id === $currentEnrollment->id)
                <div class="rounded-xl border border-surface-border dark:border-slate-800 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-medium text-slate-900 dark:text-white">{{ $enrollment->academicYear?->name }}</p>
                        <span class="badge bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $enrollment->status?->label() }}</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $enrollment->gradeLevel?->name ?? '—' }}
                        @if ($enrollment->section)
                            &middot; Section {{ $enrollment->section->name }}
                        @endif
                        @if ($enrollment->course)
                            &middot; {{ $enrollment->course->name }}
                        @endif
                        &middot; {{ $enrollment->classSchedules->count() }} class(es)
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</section>
