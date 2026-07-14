@extends('layouts.marketing')

@section('title', config('app.name', 'Class Sync') . ' — Student Monitoring System')

@section('content')
    @php
        $isInstalled = file_exists(storage_path('installed'));
        $ctaUrl =
            $isInstalled && Route::has('login')
                ? route('login')
                : (Route::has('setup.index')
                    ? route('setup.index')
                    : '#');
        $ctaLabel = $isInstalled ? 'Go to Dashboard' : 'Start Setup Wizard';
    @endphp

    <div x-data="{ mode: 'offline' }">
        {{-- Hero --}}
        <section class="mx-auto max-w-7xl px-4 pb-20 pt-12 sm:px-6 lg:px-8 lg:pb-24 lg:pt-16">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="animate-fade-up">
                    {{-- Mode switcher --}}
                    <div class="mb-6 inline-flex rounded-full border border-slate-200 bg-slate-100/80 p-1 shadow-sm"
                        role="tablist" aria-label="Deployment mode">
                        <button type="button" role="tab" @click="mode = 'offline'"
                            :aria-selected="mode === 'offline'"
                            :class="mode === 'offline'
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700'"
                            class="rounded-full px-4 py-1.5 text-sm font-semibold transition">
                            Offline
                        </button>
                        <button type="button" role="tab" @click="mode = 'online'"
                            :aria-selected="mode === 'online'"
                            :class="mode === 'online'
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700'"
                            class="rounded-full px-4 py-1.5 text-sm font-semibold transition">
                            Online
                        </button>
                    </div>

                    <div class="marketing-badge mb-6"
                        :class="mode === 'offline' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-sky-200 bg-sky-50 text-sky-800'">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                :class="mode === 'offline' ? 'bg-emerald-400' : 'bg-sky-400'"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full"
                                :class="mode === 'offline' ? 'bg-emerald-500' : 'bg-sky-500'"></span>
                        </span>
                        <span x-show="mode === 'offline'">Offline mode &middot; No internet needed</span>
                        <span x-show="mode === 'online'" x-cloak>Online mode &middot; Access from anywhere</span>
                    </div>

                    <h1
                        class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.35rem] lg:leading-[1.12]">
                        Modern student monitoring for
                        <span class="bg-gradient-to-r from-emerald-500 to-violet-500 bg-clip-text text-transparent">every
                            campus</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600" x-show="mode === 'offline'">
                        Install Class Sync on your school computer and run everything on-site. Track attendance, manage
                        student profiles, and generate reports — even when the internet is down.
                    </p>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600" x-show="mode === 'online'" x-cloak>
                        Host Class Sync on a web server and let your staff sign in from any device. Monitor attendance,
                        manage records, and share reports securely over the internet.
                    </p>

                    <div class="mt-10 flex flex-wrap gap-3">
                        <a href="{{ $ctaUrl }}" class="marketing-btn-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span x-text="mode === 'offline' ? '{{ $isInstalled ? 'Go to Dashboard' : 'Install Offline' }}' : '{{ $isInstalled ? 'Go to Dashboard' : 'Deploy Online' }}'">{{ $isInstalled ? 'Go to Dashboard' : 'Install Offline' }}</span>
                        </a>
                        @if (!$isInstalled && Route::has('login'))
                            <a href="{{ route('login') }}" class="marketing-btn-secondary">
                                I already have an account
                            </a>
                        @elseif ($isInstalled && Route::has('setup.index'))
                            <a href="{{ route('setup.index') }}" class="marketing-btn-secondary">
                                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Run setup again
                            </a>
                        @endif
                    </div>

                    {{-- Stats: Offline --}}
                    <div class="mt-12 grid grid-cols-3 gap-3 sm:gap-4" x-show="mode === 'offline'">
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">11</p>
                            <p class="text-xs text-slate-500">User roles</p>
                        </div>
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">24/7</p>
                            <p class="text-xs text-slate-500">On-site monitoring</p>
                        </div>
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">0</p>
                            <p class="text-xs text-slate-500">Internet required</p>
                        </div>
                    </div>

                    {{-- Stats: Online --}}
                    <div class="mt-12 grid grid-cols-3 gap-3 sm:gap-4" x-show="mode === 'online'" x-cloak>
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">11</p>
                            <p class="text-xs text-slate-500">User roles</p>
                        </div>
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">Any</p>
                            <p class="text-xs text-slate-500">Device access</p>
                        </div>
                        <div class="marketing-stat-card">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <p class="mt-3 text-2xl font-bold text-slate-900">24/7</p>
                            <p class="text-xs text-slate-500">Remote monitoring</p>
                        </div>
                    </div>
                </div>

                {{-- Dashboard preview mockup --}}
                <div class="animate-fade-up-delay relative">
                    <div
                        class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-emerald-200/40 via-transparent to-violet-200/40 blur-2xl">
                    </div>
                    <div class="marketing-mockup relative">
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/90 px-5 py-3.5">
                            <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-amber-400"></div>
                            <div class="h-2.5 w-2.5 rounded-full bg-emerald-400"></div>
                            <span class="ml-2 flex-1 text-center text-xs font-medium text-slate-400">
                                <span
                                    class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500 align-middle"></span>
                                Dashboard — Live
                            </span>
                        </div>

                        <div class="space-y-4 p-5 sm:p-6">
                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="rounded-2xl bg-emerald-50/80 p-3 ring-1 ring-emerald-100">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-md bg-emerald-500 text-white">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </span>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                            Present</p>
                                    </div>
                                    <p class="mt-2 text-lg font-bold text-slate-900 sm:text-xl">847</p>
                                    <p class="text-[10px] text-slate-500">Students present</p>
                                </div>
                                <div class="rounded-2xl bg-amber-50/80 p-3 ring-1 ring-amber-100">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-md bg-amber-400 text-white">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </span>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Late
                                        </p>
                                    </div>
                                    <p class="mt-2 text-lg font-bold text-slate-900 sm:text-xl">23</p>
                                    <p class="text-[10px] text-slate-500">Students late</p>
                                </div>
                                <div class="rounded-2xl bg-rose-50/80 p-3 ring-1 ring-rose-100">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-md bg-rose-500 text-white">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-rose-700">Absent
                                        </p>
                                    </div>
                                    <p class="mt-2 text-lg font-bold text-slate-900 sm:text-xl">12</p>
                                    <p class="text-[10px] text-slate-500">Students absent</p>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <div class="mb-4 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-800">Today's Attendance</span>
                                    <span class="text-sm font-bold text-emerald-600">94.2%</span>
                                </div>
                                <div class="flex h-24 items-end gap-1.5">
                                    @foreach ([35, 48, 42, 58, 72, 68, 88, 95, 82, 70, 55, 40] as $h)
                                        <div class="flex-1 rounded-t-md bg-gradient-to-t from-violet-500 to-violet-300"
                                            style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                                <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                                    <span>6 AM</span>
                                    <span>12 PM</span>
                                    <span>8 PM</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @foreach ([
                            ['Maria Santos', '07:42 AM', 'Present', 'MS', 'bg-emerald-100 text-emerald-700'],
                            ['Juan Dela Cruz', '08:15 AM', 'Late', 'JD', 'bg-amber-100 text-amber-700'],
                            ['Ana Reyes', '07:30 AM', 'Present', 'AR', 'bg-violet-100 text-violet-700'],
                        ] as [$name, $time, $status, $initials, $avatar])
                                    <div
                                        class="flex items-center justify-between rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-100">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatar }}">{{ $initials }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-800">{{ $name }}</p>
                                                <p class="text-[11px] text-slate-400">{{ $time }}</p>
                                            </div>
                                        </div>
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold shrink-0',
                                            'bg-amber-100 text-amber-700' => $status === 'Late',
                                            'bg-emerald-100 text-emerald-700' => $status === 'Present',
                                        ])>{{ $status }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-5 py-2.5 text-[11px] text-slate-400">
                            <span x-show="mode === 'offline'">Running on this computer</span>
                            <span x-show="mode === 'online'" x-cloak>Connected securely online</span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Live
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Deployment types --}}
        <section id="deployment" class="border-y border-slate-100 bg-slate-50 py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Two ways to run Class Sync</h2>
                    <p class="mt-4 text-slate-500">
                        Choose the deployment that fits your campus — switch anytime as your school grows.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-2">
                    <button type="button" @click="mode = 'offline'"
                        :class="mode === 'offline'
                            ? 'ring-2 ring-emerald-500 border-emerald-200 bg-white shadow-lg shadow-emerald-100/60'
                            : 'border-slate-200 bg-white/70 hover:border-slate-300'"
                        class="rounded-3xl border p-7 text-left transition">
                        <div class="flex items-start justify-between gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                                </svg>
                            </div>
                            <span x-show="mode === 'offline'"
                                class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selected</span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900">Offline</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            Best for schools that want full control on their own hardware. Data stays on campus and the
                            system keeps working without an internet connection.
                        </p>
                        <ul class="mt-5 space-y-2.5 text-sm text-slate-600">
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>Install on a school PC or local server</li>
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>Works during outages — no cloud dependency</li>
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>Private records managed by your school</li>
                        </ul>
                    </button>

                    <button type="button" @click="mode = 'online'"
                        :class="mode === 'online'
                            ? 'ring-2 ring-sky-500 border-sky-200 bg-white shadow-lg shadow-sky-100/60'
                            : 'border-slate-200 bg-white/70 hover:border-slate-300'"
                        class="rounded-3xl border p-7 text-left transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <span x-show="mode === 'online'" x-cloak
                                class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Selected</span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900">Online</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            Best for schools that need remote access. Host on a web server so admins, teachers, and
                            staff can monitor campus activity from anywhere.
                        </p>
                        <ul class="mt-5 space-y-2.5 text-sm text-slate-600">
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-500"></span>Access from office, home, or mobile</li>
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-500"></span>Central hosting with role-based sign-in</li>
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-500"></span>Live dashboards for your whole team</li>
                        </ul>
                    </button>
                </div>
            </div>
        </section>

        {{-- Features strip --}}
        <section id="features" class="relative overflow-hidden bg-slate-900 py-16 sm:py-20">
            <div
                class="pointer-events-none absolute -right-20 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full bg-emerald-500/10 blur-3xl">
            </div>
            <div class="pointer-events-none absolute left-10 top-0 h-40 w-40 rounded-full bg-violet-500/10 blur-3xl">
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-[1fr_auto] lg:gap-16">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            <span x-show="mode === 'offline'">
                                Built for campuses that stay
                                <span class="text-emerald-400">fully independent</span>.
                            </span>
                            <span x-show="mode === 'online'" x-cloak>
                                Built for teams that need
                                <span class="text-sky-400">always-on access</span>.
                            </span>
                        </h2>
                        <p class="mt-4 max-w-2xl text-slate-400" x-show="mode === 'offline'">
                            Keep attendance, student records, and reports running on your own machine — reliable for
                            registrars, teachers, security, and administrators.
                        </p>
                        <p class="mt-4 max-w-2xl text-slate-400" x-show="mode === 'online'" x-cloak>
                            Give every role a secure login from any browser — so monitoring continues whether staff are
                            on campus or working remotely.
                        </p>

                        {{-- Offline features --}}
                        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4" x-show="mode === 'offline'">
                            @foreach ([
                        ['icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z', 'title' => 'Live on campus', 'desc' => 'Track attendance in real time'],
                        ['icon' => 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636', 'title' => 'No internet needed', 'desc' => 'Keeps working during outages'],
                        ['icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z', 'title' => 'Private by design', 'desc' => 'Your school keeps the data'],
                        ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'title' => 'Instant reports', 'desc' => 'Export when you need them'],
                    ] as $feature)
                                <div>
                                    <div
                                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 ring-1 ring-white/10">
                                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="{{ $feature['icon'] }}" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-white">{{ $feature['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-400">{{ $feature['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        {{-- Online features --}}
                        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4" x-show="mode === 'online'" x-cloak>
                            @foreach ([
                        ['icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z', 'title' => 'Remote dashboards', 'desc' => 'Monitor campus from anywhere'],
                        ['icon' => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418', 'title' => 'Multi-device access', 'desc' => 'Desktop, tablet, or phone'],
                        ['icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z', 'title' => 'Secure sign-in', 'desc' => 'Role-based access for staff'],
                        ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'title' => 'Shared reporting', 'desc' => 'Export and review together'],
                    ] as $feature)
                                <div>
                                    <div
                                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 ring-1 ring-white/10">
                                        <svg class="h-5 w-5 text-sky-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="{{ $feature['icon'] }}" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-white">{{ $feature['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-400">{{ $feature['desc'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="hidden lg:flex items-center justify-center">
                        <div class="relative flex h-48 w-48 items-center justify-center">
                            <div class="absolute inset-0 rounded-full border"
                                :class="mode === 'offline' ? 'border-emerald-400/20' : 'border-sky-400/20'"></div>
                            <div class="absolute inset-4 rounded-full border"
                                :class="mode === 'offline' ? 'border-emerald-400/10' : 'border-sky-400/10'"></div>
                            <div class="relative flex h-28 w-28 items-center justify-center rounded-3xl shadow-2xl"
                                :class="mode === 'offline'
                                    ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/40'
                                    : 'bg-gradient-to-br from-sky-400 to-sky-600 shadow-sky-500/40'">
                                <svg x-show="mode === 'offline'" class="h-14 w-14 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                                <svg x-show="mode === 'online'" x-cloak class="h-14 w-14 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works / process --}}
        <section id="how-it-works" class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        <span x-show="mode === 'offline'">Offline setup process</span>
                        <span x-show="mode === 'online'" x-cloak>Online setup process</span>
                    </h2>
                    <p class="mt-4 text-slate-500" x-show="mode === 'offline'">
                        Get Class Sync running on your school computer in three clear steps.
                    </p>
                    <p class="mt-4 text-slate-500" x-show="mode === 'online'" x-cloak>
                        Publish Class Sync to your web host and bring your whole team online.
                    </p>
                </div>

                {{-- Offline process --}}
                <div class="mt-16 grid gap-8 md:grid-cols-3" x-show="mode === 'offline'">
                    @foreach ([
                [
                    'step' => '01',
                    'title' => 'Install on your PC',
                    'desc' => 'Launch the guided wizard on your school computer. It checks system requirements and prepares the database locally.',
                ],
                [
                    'step' => '02',
                    'title' => 'Configure your campus',
                    'desc' => 'Enter school details, create the administrator account, and set the academic year — all stored on your machine.',
                ],
                [
                    'step' => '03',
                    'title' => 'Enroll & monitor on-site',
                    'desc' => 'Add students, assign sections, record attendance, and open the live dashboard without needing the internet.',
                ],
            ] as $item)
                        <div class="relative text-center">
                            <div
                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-violet-600 text-lg font-bold text-white shadow-lg shadow-emerald-500/25">
                                {{ $item['step'] }}
                            </div>
                            <h3 class="mt-6 text-lg font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Online process --}}
                <div class="mt-16 grid gap-8 md:grid-cols-3" x-show="mode === 'online'" x-cloak>
                    @foreach ([
                [
                    'step' => '01',
                    'title' => 'Deploy to your host',
                    'desc' => 'Upload Class Sync to your web server or VPS, point the domain, and complete the setup wizard over HTTPS.',
                ],
                [
                    'step' => '02',
                    'title' => 'Invite your staff',
                    'desc' => 'Create accounts for registrars, teachers, security, and admins with the right permissions for each role.',
                ],
                [
                    'step' => '03',
                    'title' => 'Monitor from anywhere',
                    'desc' => 'Sign in from any device to track attendance, review student profiles, and generate reports in real time.',
                ],
            ] as $item)
                        <div class="relative text-center">
                            <div
                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-violet-600 text-lg font-bold text-white shadow-lg shadow-sky-500/25">
                                {{ $item['step'] }}
                            </div>
                            <h3 class="mt-6 text-lg font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section id="get-started" class="bg-slate-50 pb-24 pt-4">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border p-10 text-center shadow-lg transition"
                    :class="mode === 'offline'
                        ? 'border-emerald-100 bg-gradient-to-br from-emerald-50 to-violet-50 shadow-emerald-100/50'
                        : 'border-sky-100 bg-gradient-to-br from-sky-50 to-violet-50 shadow-sky-100/50'">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        <span x-show="mode === 'offline'">Ready to run Class Sync offline?</span>
                        <span x-show="mode === 'online'" x-cloak>Ready to run Class Sync online?</span>
                    </h2>
                    <p class="mx-auto mt-4 max-w-lg text-slate-600" x-show="mode === 'offline'">
                        Install once on your school computer. No subscription and no internet dependency for day-to-day
                        monitoring.
                    </p>
                    <p class="mx-auto mt-4 max-w-lg text-slate-600" x-show="mode === 'online'" x-cloak>
                        Host on your own web server and give your team secure access from anywhere they work.
                    </p>
                    <a href="{{ $ctaUrl }}" class="marketing-btn-primary mt-8"
                        :class="mode === 'online' && '!bg-sky-600 hover:!bg-sky-500 !shadow-sky-600/25'">
                        <span
                            x-text="mode === 'offline' ? '{{ $isInstalled ? 'Go to Dashboard' : 'Start Offline Setup' }}' : '{{ $isInstalled ? 'Go to Dashboard' : 'Start Online Setup' }}'">{{ $isInstalled ? 'Go to Dashboard' : 'Start Offline Setup' }}</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
