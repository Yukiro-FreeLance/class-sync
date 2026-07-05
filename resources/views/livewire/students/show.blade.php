@php
    $tabs = [
        'info' => [
            'label' => 'Info',
            'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
        ],
        'enrollment' => [
            'label' => 'Enrollment',
            'icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342',
        ],
        'timeline' => [
            'label' => 'Timeline',
            'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'attendance' => [
            'label' => 'Attendance',
            'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4',
        ],
        'behavior' => [
            'label' => 'Behavior',
            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        ],
        'documents' => [
            'label' => 'Documents',
            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        ],
    ];
@endphp

<div class="student-profile-page" x-data="{ copied: false }">
    {{-- Header --}}
    <div class="mb-6 no-print">
        <a href="{{ route('students.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-green-700 dark:hover:text-green-400 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Students
        </a>

        <div class="flex flex-col lg:flex-row lg:items-start gap-5 mt-4">
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <div @class([
                    'h-16 w-16 shrink-0 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg',
                    'bg-green-700 shadow-green-700/25' => ! $student->trashed(),
                    'bg-amber-600 shadow-amber-600/25' => $student->trashed(),
                ])>
                    {{ strtoupper(substr($student->first_name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ $student->full_name }}
                        </h1>
                        <x-student-status-badge :status="$student->status" :archived="$student->trashed()" />
                    </div>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="font-mono text-sm text-slate-500">{{ $student->student_number }}</span>
                        <button type="button"
                            @click="navigator.clipboard.writeText('{{ $student->student_number }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs text-slate-400 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 transition"
                            title="Copy student ID">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 1.927-.184" />
                            </svg>
                            <span x-text="copied ? 'Copied' : 'Student ID'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0 no-print">
                @unless ($student->trashed())
                    @can('update', $student)
                        <a href="{{ route('students.edit', $student) }}" wire:navigate class="btn-secondary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                            Edit
                        </a>
                    @endcan
                    @can('enroll', $student)
                        <a href="{{ route('students.enroll', $student) }}" wire:navigate class="btn-primary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 011.655-2.903 3.375 3.375 0 013.78 0 3.375 3.375 0 011.655 2.903M15 7.5H9m4.5 0V5.25A2.25 2.25 0 0011.25 3h-1.5A2.25 2.25 0 008.25 5.25V7.5" />
                            </svg>
                            Enroll
                        </a>
                    @endcan
                    @can('archive', $student)
                        <button type="button" wire:click="archive"
                            wire:confirm="Archive this student? They will be hidden from lists and attendance."
                            class="btn-secondary text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            Archive
                        </button>
                    @endcan
                @else
                    @can('restore', $student)
                        <button type="button" wire:click="restore"
                            wire:confirm="Restore this student to active records?"
                            class="btn-primary">Restore student</button>
                    @endcan
                    @can('delete', $student)
                        <button type="button" wire:click="forceDelete"
                            wire:confirm="Permanently delete this student? This cannot be undone."
                            class="btn-secondary text-red-600 dark:text-red-400 border-red-200 dark:border-red-900/50">
                            Delete permanently
                        </button>
                    @endcan
                @endunless
            </div>
        </div>
    </div>

    @if ($student->trashed())
        <div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 no-print">
            <p class="text-sm text-amber-900 dark:text-amber-100">
                This student is <strong>archived</strong>
                @if ($student->deleted_at)
                    (since {{ $student->deleted_at->format('M d, Y') }})
                @endif
                . Restore them to edit records or mark attendance again.
            </p>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1.5 mb-6 overflow-x-auto pb-1 no-print border-b border-surface-border dark:border-slate-800">
        @foreach ($tabs as $tab => $config)
            <button type="button" wire:click="setTab('{{ $tab }}')" @class([
                'inline-flex items-center gap-2 px-4 py-2.5 rounded-t-xl text-sm font-medium whitespace-nowrap transition border-b-2 -mb-px',
                'border-green-700 text-green-700 bg-green-50 dark:bg-green-900/20 dark:text-green-300 dark:border-green-500' => $activeTab === $tab,
                'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50' => $activeTab !== $tab,
            ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $config['icon'] }}" />
                </svg>
                {{ $config['label'] }}
            </button>
        @endforeach
    </div>

  <div @class([
      'grid gap-6',
      'xl:grid-cols-3' => $activeTab === 'info',
  ])>
        {{-- Main content --}}
        <div @class(['min-w-0', 'xl:col-span-2' => $activeTab === 'info'])>
            @if ($activeTab === 'info')
                <section class="panel">
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Student Information</h2>
                            <p class="text-sm text-slate-500 mt-0.5">Personal and academic details</p>
                        </div>
                        @can('update', $student)
                            <a href="{{ route('students.edit', $student) }}" wire:navigate
                                class="btn-secondary text-sm py-2 no-print">Edit Info</a>
                        @endcan
                    </div>

                    <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-6">
                        <x-student-profile-field label="Grade Level" :value="$student->gradeLevel?->name">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Section" :value="$student->section?->name">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Academic Year" :value="$student->academicYear?->name">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Track / Strand" :value="$student->course?->name">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Gender" :value="ucfirst($student->gender ?? '') ?: '—'">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="RFID Tag" :value="$student->rfid_tag">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M9 3.75V5.25A2.25 2.25 0 0 0 11.25 7.5h1.5A2.25 2.25 0 0 0 15 5.25V3.75M9 3.75h6" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Status">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </x-slot:icon>
                            <x-student-status-badge :status="$student->status" :archived="$student->trashed()" />
                        </x-student-profile-field>

                        <x-student-profile-field label="Birth Date" :value="$student->birth_date?->format('M d, Y')">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="Address" :value="$student->address" class="sm:col-span-2">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </x-slot:icon>
                        </x-student-profile-field>

                        <x-student-profile-field label="LRN No." class="sm:col-span-2">
                            <x-slot:icon>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                </svg>
                            </x-slot:icon>
                            {{-- <div class="flex flex-wrap items-center gap-3">
                                <span class="font-mono text-sm font-semibold">{{ $student->qr_code ?? $student->student_number }}</span>
                                @if ($qrCodeUrl)
                                    <img src="{{ $qrCodeUrl }}" alt="QR code" class="h-12 w-12 rounded-lg border border-surface-border dark:border-slate-700">
                                @endif
                            </div> --}}
                        </x-student-profile-field>
                    </dl>

                    @if ($student->guardians->isNotEmpty())
                        <div class="mt-8 pt-6 border-t border-surface-border dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Guardians</h3>
                            <div class="grid sm:grid-cols-2 gap-3">
                                @foreach ($student->guardians as $guardian)
                                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-4 border border-surface-border dark:border-slate-800">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $guardian->name }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $guardian->relationship }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-2">{{ $guardian->phone }}
                                            @if ($guardian->email)
                                                <span class="block text-xs text-slate-400">{{ $guardian->email }}</span>
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @elseif ($activeTab === 'enrollment')
                @include('livewire.students.partials.show-enrollment')
            @elseif ($activeTab === 'timeline')
                <section class="panel">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Activity Timeline</h2>
                    <div class="relative">
                        @forelse ($timeline as $entry)
                            <div class="flex gap-4 pb-6 last:pb-0">
                                <div class="flex flex-col items-center">
                                    <div class="h-3 w-3 rounded-full ring-4 ring-white dark:ring-slate-900"
                                        style="background-color: {{ $entry['color'] ?? '#6366f1' }}"></div>
                                    @if (! $loop->last)
                                        <div class="w-px flex-1 bg-slate-200 dark:bg-slate-700 mt-1"></div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pb-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $entry['title'] }}</p>
                                        <span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($entry['at'])->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @if (! empty($entry['description']))
                                        <p class="text-sm text-slate-500 mt-1">{{ $entry['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm py-8 text-center">No activity recorded yet.</p>
                        @endforelse
                    </div>
                </section>
            @elseif ($activeTab === 'attendance')
                <section class="panel">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Attendance Records</h2>

                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Campus gate</h3>
                    <div class="space-y-1 mb-8">
                        @forelse ($attendance as $record)
                            <div class="flex items-center justify-between py-3 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-sm font-medium">{{ $record->date?->format('M d, Y') }}</span>
                                    <span class="badge bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $record->status?->label() }}</span>
                                    @if ($record->time_in)
                                        <span class="text-xs text-slate-500">In: {{ substr((string) $record->time_in, 0, 5) }}</span>
                                    @endif
                                    @if ($record->time_out)
                                        <span class="text-xs text-slate-500">Out: {{ substr((string) $record->time_out, 0, 5) }}</span>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-400">{{ $record->method?->label() }}</span>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm py-4 text-center">No campus gate records yet.</p>
                        @endforelse
                    </div>

                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Class attendance</h3>
                    <div class="space-y-1">
                        @forelse ($classAttendance as $log)
                            <div class="flex items-center justify-between py-3 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <div class="flex items-center gap-3 flex-wrap min-w-0">
                                    <span class="text-sm font-medium">{{ $log->date?->format('M d, Y') }}</span>
                                    <span class="badge" style="background-color: {{ $log->remark?->color }}20; color: {{ $log->remark?->color }}">
                                        {{ $log->remark?->label }}
                                    </span>
                                    <span class="text-xs text-slate-500 truncate">
                                        {{ $log->classSchedule?->subject?->name ?? 'Class' }}
                                        @if ($log->section)
                                            · {{ $log->section->gradeLevel?->name }} {{ $log->section->name }}
                                        @endif
                                    </span>
                                    @if ($log->remarks)
                                        <span class="text-xs text-slate-400 truncate">{{ $log->remarks }}</span>
                                    @endif
                                </div>
                                @if ($log->classSchedule?->starts_at)
                                    <span class="text-xs text-slate-400 shrink-0">{{ substr((string) $log->classSchedule->starts_at, 0, 5) }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm py-4 text-center">No class attendance records yet.</p>
                        @endforelse
                    </div>
                </section>
            @elseif ($activeTab === 'behavior')
                <section class="panel">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Behavior Records</h2>
                    <div class="space-y-3">
                        @forelse ($behaviorRecords as $record)
                            <div class="rounded-xl border border-surface-border dark:border-slate-800 p-4">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $record->type }}</p>
                                <p class="text-sm text-slate-500 mt-1">{{ $record->description }}</p>
                                <p class="text-xs text-slate-400 mt-2">{{ $record->date?->format('M d, Y') }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm py-8 text-center">No behavior records yet.</p>
                        @endforelse
                    </div>
                </section>
            @else
                <section class="panel">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">Documents</h2>
                    <div class="space-y-2">
                        @forelse ($documents as $document)
                            <div class="flex items-center justify-between py-3 px-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ $document->title }}</span>
                                </div>
                                <span class="text-xs text-slate-400">{{ $document->created_at?->format('M d, Y') }}</span>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm py-8 text-center">No documents uploaded yet.</p>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>

        {{-- Sidebar (Info tab) --}}
        @if ($activeTab === 'info')
            <aside class="space-y-5 no-print">
                {{-- Quick Summary --}}
                <section class="panel">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Quick Summary</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 p-3 border border-emerald-100 dark:border-emerald-900/30">
                            <div class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 mb-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                                </svg>
                            </div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Grade Level</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5">{{ $student->gradeLevel?->name ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-3 border border-blue-100 dark:border-blue-900/30">
                            <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-700 dark:text-blue-300 mb-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                            </div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Section</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5">{{ $student->section?->name ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl bg-violet-50 dark:bg-violet-900/20 p-3 border border-violet-100 dark:border-violet-900/30">
                            <div class="h-8 w-8 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-700 dark:text-violet-300 mb-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Academic Year</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5">{{ $student->academicYear?->name ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-3 border border-amber-100 dark:border-amber-900/30">
                            <div class="h-8 w-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-700 dark:text-amber-300 mb-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Status</p>
                            <div class="mt-1">
                                <x-student-status-badge :status="$student->status" :archived="$student->trashed()" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Student Actions --}}
                <section class="panel">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Student Actions</h3>
                    <div class="space-y-2">
                        @php
                            $guardianEmail = $student->guardians->first()?->email;
                        @endphp
                        @if ($guardianEmail)
                            <a href="mailto:{{ $guardianEmail }}"
                                class="flex items-center gap-3 w-full rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Send Message
                            </a>
                        @else
                            <button type="button" disabled
                                class="flex items-center gap-3 w-full rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-surface-border dark:border-slate-800 px-4 py-3 text-sm font-medium text-slate-400 cursor-not-allowed">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Send Message
                            </button>
                        @endif
                        <button type="button" onclick="window.print()"
                            class="flex items-center gap-3 w-full rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 px-4 py-3 text-sm font-medium text-blue-800 dark:text-blue-200 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.22H7.231c-.662 0-1.18-.55-1.12-1.22L6.34 18m11.318 0h1.091a2.25 2.25 0 0 0 2.25-2.25V9.75a2.25 2.25 0 0 0-2.25-2.25h-1.091M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.75A2.25 2.25 0 0 1 5.25 7.5h1.091m11.318 0H18a2.25 2.25 0 0 1 2.25 2.25v6.75A2.25 2.25 0 0 1 18 18h-1.091" />
                            </svg>
                            Print Profile
                        </button>
                        <a href="{{ $exportUrl }}"
                            class="flex items-center gap-3 w-full rounded-xl bg-violet-50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-900/30 px-4 py-3 text-sm font-medium text-violet-800 dark:text-violet-200 hover:bg-violet-100 dark:hover:bg-violet-900/30 transition">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Export Data
                        </a>
                    </div>
                </section>

                {{-- Notes --}}
                <section class="panel">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notes</h3>
                        @can('update', $student)
                            <a href="{{ route('students.edit', $student) }}" wire:navigate
                                class="text-xs font-medium text-amber-700 dark:text-amber-400 hover:underline">Add Note</a>
                        @endcan
                    </div>
                    @if ($student->medical_notes)
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $student->medical_notes }}</p>
                    @else
                        <p class="text-sm text-slate-400 italic">No notes added yet.</p>
                    @endif
                </section>
            </aside>
        @endif
    </div>

    <p class="text-center text-xs text-slate-400 mt-8 print-only">
        Last updated: {{ $student->updated_at?->format('M d, Y') }} &middot; {{ $student->updated_at?->format('g:i A') }}
    </p>
    <p class="text-center text-xs text-slate-400 mt-8 no-print">
        Last updated: {{ $student->updated_at?->format('M d, Y') }} &middot; {{ $student->updated_at?->format('g:i A') }}
    </p>

    <style>
        @media print {
            .no-print,
            .app-sidebar,
            .app-topbar,
            aside.no-print {
                display: none !important;
            }

            .student-profile-page .panel {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }

            .xl\:col-span-2 {
                grid-column: span 3 / span 3;
            }
        }
    </style>
</div>
