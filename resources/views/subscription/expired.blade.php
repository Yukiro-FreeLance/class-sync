<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>System Unavailable — {{ config('app.name', 'Class Sync') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans antialiased bg-slate-950 text-slate-100">
    <div class="relative flex min-h-full items-center justify-center px-6 py-16 overflow-hidden">
        <div class="pointer-events-none absolute -top-40 -left-24 h-[28rem] w-[28rem] rounded-full bg-rose-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-slate-500/20 blur-3xl"></div>

        <div class="relative w-full max-w-lg text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 ring-1 ring-white/10">
                <svg class="h-8 w-8 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>

            <p class="text-sm font-medium uppercase tracking-[0.2em] text-rose-300/80 mb-3">System Offline</p>
            <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">This page is down</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-300">
                {{ $message }}
            </p>

            @if ($schoolName)
                <p class="mt-2 text-sm text-slate-500">{{ $schoolName }}</p>
            @endif

            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-8">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
                        Sign out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                    class="mt-8 inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
                    Back to sign in
                </a>
            @endauth
        </div>
    </div>
</body>

</html>
