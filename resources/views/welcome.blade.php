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
        $ctaLabel = $isInstalled ? 'Sign In to Dashboard' : 'Start Setup Wizard';
    @endphp

    {{-- Hero --}}
    <section class="mx-auto max-w-7xl px-4 pb-16 pt-12 sm:px-6 lg:px-8 lg:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
            <div class="animate-fade-up">
                <div class="marketing-badge mb-6">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Offline-first &middot; LAN-ready &middot; No cloud required
                </div>

                <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.25rem] lg:leading-[1.15]">
                    Modern student monitoring for
                    <span class="bg-gradient-to-r from-green-700 to-violet-500 bg-clip-text text-transparent">every</span>
                    campus
                </h1>

                <p class="mt-6 text-lg leading-relaxed text-slate-600">
                    Track attendance with QR codes and RFID, manage student profiles, generate reports, and monitor your
                    school in real-time — from a single PC or across your entire LAN.
                </p>

                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="{{ $ctaUrl }}" class="marketing-btn-primary">
                        {{ $ctaLabel }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    @if (!$isInstalled && Route::has('login'))
                        <a href="{{ route('login') }}" class="marketing-btn-secondary">
                            I already have an account
                        </a>
                    @elseif ($isInstalled && Route::has('setup.index'))
                        <a href="{{ route('setup.index') }}" class="marketing-btn-secondary">
                            Run setup again
                        </a>
                    @endif
                </div>

                <dl class="mt-12 grid grid-cols-3 gap-6 border-t border-slate-200 pt-8">
                    <div>
                        <dt class="text-2xl font-bold text-slate-900">11</dt>
                        <dd class="mt-1 text-xs text-slate-500">User roles</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-bold text-slate-900">24/7</dt>
                        <dd class="mt-1 text-xs text-slate-500">Live monitoring</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-bold text-slate-900">0</dt>
                        <dd class="mt-1 text-xs text-slate-500">Internet required</dd>
                    </div>
                </dl>
            </div>

            {{-- Dashboard preview mockup --}}
            <div class="animate-fade-up-delay relative">
                <div class="marketing-mockup">
                    <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5 bg-slate-50/80">
                        <div class="h-3 w-3 rounded-full bg-red-400"></div>
                        <div class="h-3 w-3 rounded-full bg-amber-400"></div>
                        <div class="h-3 w-3 rounded-full bg-emerald-400"></div>
                        <span class="ml-2 flex-1 text-center text-xs font-medium text-slate-400">Dashboard — Live</span>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Present</p>
                                <p class="text-xl font-bold text-emerald-600 mt-0.5">847</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Late</p>
                                <p class="text-xl font-bold text-amber-500 mt-0.5">23</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Absent</p>
                                <p class="text-xl font-bold text-red-500 mt-0.5">12</p>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-semibold text-slate-800">Today's Attendance</span>
                                <span class="text-sm font-bold text-emerald-600">94.2%</span>
                            </div>
                            <div class="flex items-end gap-1.5 h-20">
                                @foreach ([40, 65, 55, 80, 70, 90, 85] as $h)
                                    <div class="flex-1 rounded-t-md bg-brand-300/70" style="height: {{ $h }}%">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach ([['Maria Santos', '07:42 AM', 'Present'], ['Juan Dela Cruz', '08:15 AM', 'Late'], ['Ana Reyes', '07:30 AM', 'Present']] as [$name, $time, $status])
                                <div
                                    class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-100">
                                    <span class="text-sm font-medium text-slate-800">{{ $name }}</span>
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xs text-slate-400">{{ $time }}</span>
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-amber-100 text-amber-700' => $status === 'Late',
                                            'bg-emerald-100 text-emerald-700' => $status === 'Present',
                                        ])>{{ $status }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-slate-800 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-white sm:text-4xl tracking-tight">Everything your school needs</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-400">A complete monitoring platform designed for registrars,
                    teachers, security, and administrators.</p>
            </div>

            <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
            ['icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z', 'title' => 'Attendance Tracking', 'desc' => 'Manual entry, QR codes, RFID scanners, and live campus monitoring with instant alerts.'],
            ['icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z', 'title' => 'Student Management', 'desc' => 'Profiles, guardians, medical notes, documents, QR ID cards, and behavior records.'],
            ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'title' => 'Analytics & Reports', 'desc' => 'Daily, weekly, and yearly reports with PDF, Excel, and CSV export.'],
            ['icon' => 'M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z', 'title' => 'LAN Multi-User', 'desc' => 'Deploy on one server — teachers, registrars, and security access the same database.'],
            ['icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'title' => 'Role-Based Access', 'desc' => '11 roles with granular permissions — from administrators to parents and students.'],
            ['icon' => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75', 'title' => 'Backup & Audit', 'desc' => 'Automatic database backups, restore tools, and full activity audit logs.'],
        ] as $feature)
                    <div class="marketing-feature-card">
                        <div
                            class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500/20 ring-1 ring-brand-400/20">
                            <svg class="h-5 w-5 text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-20 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Up and running in minutes</h2>
                <p class="mt-4 text-slate-500">Our guided setup wizard handles everything automatically.</p>
            </div>

            <div class="mt-16 grid gap-8 md:grid-cols-3">
                @foreach ([['step' => '01', 'title' => 'Install & Configure', 'desc' => 'Run the 7-step wizard — system check, database setup, and school configuration.'], ['step' => '02', 'title' => 'Enroll Students', 'desc' => 'Import or add students, assign sections, and print QR ID cards in bulk.'], ['step' => '03', 'title' => 'Start Monitoring', 'desc' => 'Scan attendance at gates, view live dashboards, and send parent alerts.']] as $item)
                    <div class="relative text-center">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-violet-600 text-lg font-bold text-white shadow-lg shadow-brand-500/30">
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
    <section class="pb-24 pt-4 bg-slate-50">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div
                class="rounded-3xl border border-brand-100 bg-gradient-to-br from-brand-50 to-violet-50 p-10 text-center shadow-lg shadow-brand-100/50">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl tracking-tight">Ready to modernize your school?
                </h2>
                <p class="mx-auto mt-4 max-w-lg text-slate-600">
                    Works on a single computer or across your entire school network. No subscription, no internet
                    dependency.
                </p>
                <a href="{{ $ctaUrl }}" class="marketing-btn-primary mt-8">
                    {{ $ctaLabel }}
                </a>
            </div>
        </div>
    </section>
@endsection
