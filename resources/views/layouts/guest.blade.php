<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sign In' }} — {{ config('app.name', 'Class Sync') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full font-sans antialiased">
    <div class="min-h-full lg:grid lg:grid-cols-2">
        {{-- Brand panel --}}
        <div class="auth-panel">
            <div class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-brand-500/15 blur-3xl">
            </div>
            <div
                class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-violet-500/15 blur-3xl">
            </div>

            <div class="relative">
                <a href="{{ url('/') }}" class="flex items-center gap-3" wire:navigate>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">{{ config('app.name', 'Class Sync') }}</span>
                </a>
            </div>

            <div class="relative flex flex-1 flex-col justify-center py-12 space-y-6">
                <h2 class="text-3xl font-bold leading-tight text-white lg:text-4xl">
                    Smart attendance.<br>
                    Complete visibility.
                </h2>
                <p class="max-w-md text-slate-400 leading-relaxed">
                    Monitor student attendance in real time, manage profiles, and generate reports — all from one
                    secure platform — offline on campus or online from anywhere.
                </p>

                <ul class="space-y-3.5">
                    @foreach (['Real-time attendance tracking', 'Live campus monitoring dashboard', 'Multi-role access for your entire staff'] as $item)
                        <li class="flex items-center gap-3 text-sm text-slate-300">
                            <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ config('app.name', 'Class Sync') }}
                v{{ config('classsync.version', '1.0.0') }}
            </p>
        </div>

        {{-- Form panel --}}
        <div class="flex min-h-full flex-col justify-center bg-white px-4 py-12 sm:px-6 lg:px-16 xl:px-24">
            <div class="mx-auto w-full max-w-md">
                {{-- Mobile logo --}}
                <div class="mb-8 lg:hidden text-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5" wire:navigate>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-700">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900">{{ config('app.name', 'Class Sync') }}</span>
                    </a>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
