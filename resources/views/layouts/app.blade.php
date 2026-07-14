<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Class Sync') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            {{ app(\App\Services\Settings\BrandingService::class)->layoutCssVariableString() }};
        }

        .dark {
            --app-sidebar-bg: #0f172a;
            --app-header-bg: rgba(15, 23, 42, 0.92);
            --app-main-bg: #020617;
            --app-sidebar-brand-bg: rgba(15, 23, 42, 0.6);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
</head>

<body x-data="appShell()" x-init="init()" class="font-sans antialiased min-h-screen">
    <div class="flex min-h-screen">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" x-cloak></div>

        {{-- Sidebar --}}
        <aside :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            sidebarNarrow ? 'w-[4.5rem]' : 'w-[260px]',
        ]" class="app-sidebar transition-all duration-300 ease-in-out">
            <x-app-sidebar-brand
                :logo-url="$branding['logo_url'] ?? null"
                :acronym="$branding['sidebar_acronym'] ?? 'CS'"
                :school-name="$branding['school_name'] ?? config('app.name')"
                :school-code="$branding['school_code'] ?? null"
                :app-subtitle="$branding['app_subtitle'] ?? 'Class Sync'"
                :role-labels="auth()->check() ? app(\App\Services\Navigation\SidebarNavigationService::class)->roleLabelsFor(auth()->user()) : []"
            />

            <nav :class="sidebarNarrow ? 'px-2' : 'px-3'"
                class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-0.5">
                @auth
                    @php $sidebarNav = app(\App\Services\Navigation\SidebarNavigationService::class); @endphp
                    <x-app-sidebar-nav :items="$sidebarNav->itemsFor(auth()->user())" :navigation="$sidebarNav" />
                @endauth
            </nav>

            <div class="hidden lg:block border-t border-surface-border dark:border-slate-800 p-2">
                <button type="button" @click="toggleSidebarCollapse()"
                    class="nav-link nav-link-inactive w-full"
                    :class="sidebarNarrow ? 'justify-center px-2' : ''"
                    :title="sidebarNarrow ? 'Expand sidebar' : 'Collapse sidebar'">
                    <svg class="h-5 w-5 shrink-0 transition-transform duration-300"
                        :class="sidebarNarrow ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <span x-show="!sidebarNarrow" x-cloak>Collapse</span>
                </button>
            </div>

            {{-- <div class="p-4 space-y-4 border-t border-surface-border dark:border-slate-800">
                <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100/80 dark:from-brand-900/30 dark:to-brand-800/20 p-4 border border-brand-100 dark:border-brand-800/40">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-8 w-8 rounded-lg bg-green-700 flex items-center justify-center">
                            <svg class="h-4 w-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 17.4 5.7 21l2.3-7-6-4.6h7.6z" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-800 dark:text-white">Secret</p>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-3 leading-relaxed">Unlock advanced analytics, SMS alerts, and multi-campus support.</p>
                    <button type="button" class="w-full btn-primary text-xs py-2">Learn More</button>
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center">
                    &copy; {{ date('Y') }} Class Sync &middot; v1.0.0
                </p>
            </div> --}}
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0 app-main">
            <x-subscription-expiry-banner :banner="$subscriptionExpiryBanner ?? null" />

            <header class="app-topbar">
                <div class="flex items-center gap-4 px-4 sm:px-6 py-3">
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button @click="commandOpen = true"
                        class="hidden sm:flex items-center gap-2 flex-1 max-w-md px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-surface-border dark:border-slate-700 text-sm text-slate-500 dark:text-slate-400 hover:border-brand-300 dark:hover:border-green-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Search...</span>
                        <kbd
                            class="ml-auto text-[10px] bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-surface-border dark:border-slate-600 text-slate-400">ctrl
                            + k</kbd>
                    </button>

                    <div class="flex items-center gap-1 ml-auto">
                        <button
                            class="relative p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-slate-600 dark:text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">3</span>
                        </button>

                        <button @click="toggleDark()"
                            class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-slate-600 dark:text-slate-400">
                            <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="dark" x-cloak class="h-5 w-5 text-amber-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <div
                                        class="h-9 w-9 rounded-full bg-green-700 flex items-center justify-center text-white text-sm font-semibold ring-2 ring-brand-100 dark:ring-brand-900">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <span
                                        class="hidden md:block text-sm font-medium text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</span>
                                    <svg class="hidden md:block h-4 w-4 text-slate-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('help.index')" wire:navigate>{{ __('Help & Manual') }}</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Command Palette --}}
    <div x-show="commandOpen" x-transition class="fixed inset-0 z-[60] flex items-start justify-center pt-[15vh] px-4"
        x-cloak>
        <div @click="commandOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-lg panel shadow-2xl overflow-hidden p-0">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-surface-border dark:border-slate-800">
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input x-model="commandQuery" type="text" placeholder="Type a command or search..."
                    class="flex-1 bg-transparent border-0 focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400 text-sm"
                    @keydown.escape="commandOpen = false">
            </div>
            <div class="max-h-64 overflow-y-auto py-2">
                <template x-for="cmd in commands" :key="cmd.href">
                    <a :href="cmd.href" @click="commandOpen = false"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-brand-50 dark:hover:bg-brand-900/20 text-slate-700 dark:text-slate-300">
                        <span x-text="cmd.label"></span>
                    </a>
                </template>
                <p x-show="commands.length === 0" class="px-4 py-6 text-sm text-slate-500 text-center">No results
                    found</p>
            </div>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div class="fixed bottom-4 right-4 z-[70] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition
                :class="{
                    'bg-emerald-600': toast.type === 'success',
                    'bg-red-600': toast.type === 'error',
                    'bg-green-700': toast.type === 'info',
                    'bg-amber-600': toast.type === 'warning',
                }"
                class="pointer-events-auto rounded-xl px-4 py-3 text-white text-sm shadow-lg flex items-center justify-between gap-3">
                <span x-text="toast.message"></span>
                <button @click="removeToast(toast.id)" class="opacity-70 hover:opacity-100">&times;</button>
            </div>
        </template>
    </div>

    @livewireScripts
    @auth
        <script>
            window.__classSyncCommands = @json(app(\App\Services\Navigation\SidebarNavigationService::class)->commandItemsFor(auth()->user()));
        </script>
    @endauth
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
</body>

</html>
