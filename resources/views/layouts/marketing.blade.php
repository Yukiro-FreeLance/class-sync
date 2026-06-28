<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="Class Sync — Offline-first student monitoring, attendance tracking, and school management for single PC or LAN deployments.">

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
        $ctaLabel = $isInstalled ? 'Dashboard' : 'Get Started';
    @endphp

    <div class="marketing-page relative overflow-hidden">
        {{-- Ambient glow --}}
        <div
            class="pointer-events-none absolute -top-32 -left-32 h-[32rem] w-[32rem] rounded-full bg-brand-400/20 blur-3xl">
        </div>
        <div class="pointer-events-none absolute top-1/4 -right-24 h-96 w-96 rounded-full bg-violet-400/15 blur-3xl">
        </div>
        <div class="pointer-events-none absolute bottom-0 left-1/4 h-80 w-80 rounded-full bg-blue-400/10 blur-3xl">
        </div>

        <nav class="marketing-nav">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 ring-1 ring-brand-200">
                        <svg class="h-5 w-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-slate-800">{{ config('app.name', 'Class Sync') }}</span>
                </a>

                <div class="flex items-center gap-3">
                    @if ($isInstalled && Route::has('login'))
                        <a href="{{ route('login') }}"
                            class="hidden sm:inline-flex text-sm font-medium text-slate-600 hover:text-green-700 transition">
                            Sign in
                        </a>
                    @endif
                    <a href="{{ $ctaUrl }}" class="marketing-btn-primary !py-2 !px-5 text-sm">
                        {{ $ctaLabel }}
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
                        offline-first, LAN-ready.
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
