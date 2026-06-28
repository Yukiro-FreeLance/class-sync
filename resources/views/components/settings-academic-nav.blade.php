<nav class="flex flex-wrap gap-2 mb-6 border-b border-surface-border dark:border-slate-800 pb-4">
    @foreach ([
        ['route' => 'settings.academic.structure', 'label' => 'Departments & Grades'],
        ['route' => 'settings.academic.years', 'label' => 'Academic Years'],
        ['route' => 'settings.academic.sections', 'label' => 'Sections'],
        ['route' => 'settings.academic.rooms', 'label' => 'Rooms'],
        ['route' => 'settings.academic.subjects', 'label' => 'Subjects'],
        ['route' => 'settings.academic.schedules', 'label' => 'Schedules'],
    ] as $item)
        <a href="{{ route($item['route']) }}" wire:navigate @class([
            'px-3 py-1.5 rounded-lg text-sm font-medium transition',
            'bg-green-700 text-white' => request()->routeIs($item['route']),
            'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' => !request()->routeIs($item['route']),
        ])>{{ $item['label'] }}</a>
    @endforeach
</nav>
