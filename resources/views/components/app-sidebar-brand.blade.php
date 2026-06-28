@props([
    'logoUrl' => null,
    'acronym' => 'CS',
    'schoolName' => config('app.name'),
    'schoolCode' => null,
    'appSubtitle' => 'Class Sync',
    'roleLabels' => [],
])

<div {{ $attributes->class([
    'app-sidebar-brand border-b border-surface-border dark:border-slate-800',
]) }}>
    <div class="flex flex-col items-center px-3 py-5 text-center"
        :class="sidebarNarrow ? 'py-4' : 'py-5'">
        <div class="transition-transform duration-200" :class="sidebarNarrow ? 'scale-90' : ''">
            <x-app-brand-mark
                :logo-url="$logoUrl"
                :acronym="$acronym"
                :school-name="$schoolName"
                size="lg"
                framed
            />
        </div>

        <div x-show="!sidebarNarrow" x-transition.opacity.duration.200ms class="mt-3 w-full space-y-2">
            @if ($schoolCode)
                <p class="font-bold text-base uppercase tracking-wider text-slate-900 dark:text-white leading-tight"
                    title="{{ $schoolCode }}">
                    {{ $schoolCode }}
                </p>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 leading-snug line-clamp-2"
                    title="{{ $schoolName }}">
                    {{ $schoolName }}
                </p>
            @else
                <p class="font-bold text-sm uppercase tracking-wide text-slate-900 dark:text-white leading-snug line-clamp-2"
                    title="{{ $schoolName }}">
                    {{ $schoolName }}
                </p>
            @endif

            <p
                class="inline-flex items-center justify-center rounded-full bg-white/90 dark:bg-slate-800/90 px-2.5 py-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 ring-1 ring-slate-200/70 dark:ring-slate-700">
                {{ $appSubtitle }}
            </p>

            @if ($roleLabels !== [])
                <div class="flex flex-wrap items-center justify-center gap-1">
                    @foreach ($roleLabels as $role)
                        <span
                            class="inline-flex items-center rounded-full bg-green-100/90 dark:bg-green-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-800 dark:text-green-300 ring-1 ring-green-200/70 dark:ring-green-800/50">
                            {{ $role }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
