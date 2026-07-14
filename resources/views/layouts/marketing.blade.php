<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="Class Sync — Student monitoring for schools. Run offline on your campus computer or online for remote access.">

    <title>@yield('title', config('app.name', 'Class Sync'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>

<body class="h-full font-sans">
    @php
        $isInstalled = file_exists(storage_path('installed'));
        $ctaUrl =
            $isInstalled && Route::has('login')
                ? route('login')
                : (Route::has('setup.index')
                    ? route('setup.index')
                    : '#');
        $ctaLabel = $isInstalled ? 'Go to Dashboard' : 'Get Started';
    @endphp

    <div class="marketing-page relative overflow-hidden">
        {{-- Soft ambient shapes --}}
        <div
            class="pointer-events-none absolute -top-40 -left-40 h-[36rem] w-[36rem] rounded-full bg-emerald-300/25 blur-3xl">
        </div>
        <div class="pointer-events-none absolute top-16 right-0 h-[28rem] w-[28rem] rounded-full bg-violet-300/20 blur-3xl">
        </div>
        <div class="pointer-events-none absolute top-[40%] left-1/3 h-72 w-72 rounded-full bg-emerald-200/15 blur-3xl">
        </div>

        <nav class="marketing-nav">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0">
                    <span class="relative flex h-8 w-8 items-center justify-center" aria-hidden="true">
                        <span class="absolute left-0 top-0 h-3.5 w-3.5 rounded-sm bg-emerald-500 shadow-sm"></span>
                        <span class="absolute left-2 top-2 h-3.5 w-3.5 rounded-sm bg-white ring-1 ring-slate-200 shadow-sm"></span>
                        <span class="absolute left-4 top-4 h-3.5 w-3.5 rounded-sm bg-violet-500 shadow-sm"></span>
                    </span>
                    <span class="text-lg font-bold tracking-tight text-slate-900">{{ config('app.name', 'Class Sync') }}</span>
                </a>

                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                    <a href="#deployment" class="hover:text-slate-900 transition">Offline & Online</a>
                    <a href="#features" class="hover:text-slate-900 transition">Features</a>
                    <a href="#how-it-works" class="hover:text-slate-900 transition">How it works</a>
                    <a href="#get-started" class="hover:text-slate-900 transition">Get started</a>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    @if ($isInstalled && Route::has('login'))
                        <a href="{{ route('login') }}"
                            class="hidden sm:inline-flex text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            Sign in
                        </a>
                    @endif
                    <a href="{{ $ctaUrl }}" class="marketing-btn-primary !py-2.5 !px-5 text-sm">
                        {{ $ctaLabel }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            @yield('content')
        </main>

        <footer class="relative z-10 border-t border-slate-200 bg-white py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <p class="text-sm text-slate-500">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Class Sync') }}. Built for schools —
                        offline on campus or online anywhere.
                    </p>
                    <p class="text-xs text-slate-400">
                        v{{ config('classsync.version', '1.0.0') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
