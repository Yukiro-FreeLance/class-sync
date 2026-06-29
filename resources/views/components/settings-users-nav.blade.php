<nav class="flex flex-wrap gap-2 mb-6 border-b border-surface-border dark:border-slate-800 pb-4">

    @foreach ([

        ['route' => 'settings.users.index', 'label' => 'Users'],

        ... (auth()->user()?->can('manageRoles', \App\Models\User::class) ? [['route' => 'settings.users.roles', 'label' => 'Roles & Restrictions']] : []),

    ] as $item)

        <a href="{{ route($item['route']) }}" wire:navigate @class([

            'px-3 py-1.5 rounded-lg text-sm font-medium transition',

            'bg-green-700 text-white' => request()->routeIs($item['route']) || ($item['route'] === 'settings.users.index' && request()->routeIs('settings.users.create', 'settings.users.edit')),

            'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' => !request()->routeIs($item['route']) && !($item['route'] === 'settings.users.index' && request()->routeIs('settings.users.create', 'settings.users.edit')),

        ])>{{ $item['label'] }}</a>

    @endforeach

</nav>

