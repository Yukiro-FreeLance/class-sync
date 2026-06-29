<div>
    <x-page-header title="Students" subtitle="Manage student records">
        <x-slot name="actions">
            <div x-data="{ exportOpen: false, moreOpen: false }" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" class="btn-secondary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                    <div x-show="exportOpen" @click.outside="exportOpen = false" x-cloak
                        class="absolute right-0 mt-2 w-44 rounded-xl border border-surface-border dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg py-1 z-20">
                        <a href="{{ $this->exportUrl('xlsx') }}"
                            class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Excel
                            (.xlsx)</a>
                        <a href="{{ $this->exportUrl('csv') }}"
                            class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">CSV
                            (.csv)</a>
                    </div>
                </div>
                @can('create', \App\Models\Student::class)
                    <button type="button" wire:click="openImport" class="btn-secondary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        Import
                    </button>
                @endcan
                <div class="relative hidden sm:block">
                    <button type="button" @click="moreOpen = !moreOpen" class="btn-secondary">
                        More
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="moreOpen" @click.outside="moreOpen = false" x-cloak
                        class="absolute right-0 mt-2 w-48 rounded-xl border border-surface-border dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg py-1 z-20">
                        <a href="{{ route('students.enrollment') }}" wire:navigate @click="moreOpen = false"
                            class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Bulk
                            Enrollment</a>
                        <a href="{{ route('students.lists.master') }}" wire:navigate @click="moreOpen = false"
                            class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Master
                            List</a>
                        <a href="{{ route('students.lists.class') }}" wire:navigate @click="moreOpen = false"
                            class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Class
                            List</a>
                    </div>
                </div>
                <div class="flex sm:hidden gap-2">
                    <a href="{{ route('students.enrollment') }}" wire:navigate class="btn-secondary text-xs px-3">Enroll</a>
                </div>
                @can('create', \App\Models\Student::class)
                    <a href="{{ route('students.create') }}" wire:navigate class="btn-primary">Add Student</a>
                @endcan
            </div>
        </x-slot>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active students</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($stats['active']) }}</p>
        </div>
        @if ($canManageLifecycle)
            <button type="button" wire:click="setRecordFilter('archived')"
                class="stat-card text-left hover:border-amber-300 dark:hover:border-amber-700 transition {{ $showArchived ? 'ring-2 ring-amber-400/50 border-amber-200 dark:border-amber-800' : '' }}">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Archived</p>
                <p class="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">{{ number_format($stats['archived']) }}</p>
            </button>
        @else
            <div class="stat-card">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Showing</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $students->total() }}</p>
            </div>
        @endif
        <div class="stat-card col-span-2 hidden lg:block">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Quick tip</p>
            <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                Use the <strong>⋮</strong> menu on each row for edit, archive, restore, or delete. Destructive actions require confirmation.
            </p>
        </div>
    </div>

    {{-- Record tabs + filters --}}
    <div class="panel mb-6 space-y-4">
        @if ($canManageLifecycle)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="setRecordFilter('active')" @class([
                    'px-4 py-2 rounded-xl text-sm font-medium transition',
                    'bg-green-700 text-white shadow-sm' => ! $showArchived,
                    'btn-secondary' => $showArchived,
                ])>
                    Active records
                    <span @class([
                        'ml-1.5 rounded-full px-2 py-0.5 text-xs',
                        ! $showArchived ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700',
                    ])>{{ number_format($stats['active']) }}</span>
                </button>
                <button type="button" wire:click="setRecordFilter('archived')" @class([
                    'px-4 py-2 rounded-xl text-sm font-medium transition',
                    'bg-amber-600 text-white shadow-sm' => $showArchived,
                    'btn-secondary' => ! $showArchived,
                ])>
                    Archived
                    <span @class([
                        'ml-1.5 rounded-full px-2 py-0.5 text-xs',
                        $showArchived ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700',
                    ])>{{ number_format($stats['archived']) }}</span>
                </button>
            </div>
        @endif

        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Search by name, ID, or RFID..." class="input-field">
            </div>
            <select wire:model.live="department" class="select-field">
                <option value="">All Departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="grade" class="select-field">
                <option value="">All Grades</option>
                @foreach ($grades as $gradeLevel)
                    <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="section" class="select-field">
                <option value="">All Sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select wire:model.live="status" class="select-field w-full sm:w-48" @disabled($showArchived)>
                <option value="">All Statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($search || $department || $grade || $section || $status)
                <button wire:click="clearFilters" class="text-sm text-green-700 hover:text-brand-500 font-medium">
                    Clear filters
                </button>
            @endif
            @if ($showArchived)
                <span class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 px-2.5 py-1 rounded-lg">
                    Viewing archived students only
                </span>
            @endif
            <div class="ml-auto flex items-center gap-2">
                <label for="perPage" class="text-xs text-slate-500 whitespace-nowrap">Rows</label>
                <select wire:model.live="perPage" id="perPage" class="select-field w-20 py-1.5 text-sm">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="panel-flush">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <x-sortable-th field="name" label="Student" :sort="$sort" :direction="$direction" />
                        <x-sortable-th field="grade" label="Grade / Section" :sort="$sort" :direction="$direction" />
                        <x-sortable-th field="status" label="Status" :sort="$sort" :direction="$direction" />
                        <th class="text-right w-32">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr wire:key="student-{{ $student->id }}" @class([
                            'opacity-75' => $student->trashed(),
                        ])>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div @class([
                                        'h-9 w-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-semibold',
                                        'bg-green-700 text-white' => ! $student->trashed(),
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' => $student->trashed(),
                                    ])>
                                        {{ strtoupper(substr($student->first_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900 dark:text-white truncate">
                                            {{ $student->full_name }}
                                        </p>
                                        <p class="font-mono text-xs text-slate-500">{{ $student->student_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm text-slate-900 dark:text-white">{{ $student->gradeLevel?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">Section {{ $student->section?->name ?? '—' }}</p>
                            </td>
                            <td>
                                <x-student-status-badge :status="$student->status" :archived="$student->trashed()" />
                            </td>
                            <td class="text-right">
                                <x-student-row-actions :student="$student" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-16">
                                @if ($showArchived)
                                    <div class="max-w-sm mx-auto">
                                        <p class="text-slate-500 mb-1">No archived students</p>
                                        <p class="text-sm text-slate-400 mb-4">Archived records appear here after you archive a student from their profile or row menu.</p>
                                        <button type="button" wire:click="setRecordFilter('active')" class="btn-secondary text-sm">Back to active records</button>
                                    </div>
                                @else
                                    <p class="text-slate-500 mb-3">No students match your filters.</p>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        @can('create', \App\Models\Student::class)
                                            <button type="button" wire:click="openImport" class="btn-secondary text-sm">Import students</button>
                                            <a href="{{ route('students.create') }}" wire:navigate class="btn-primary text-sm">Add student</a>
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages() || $students->total() > 0)
            <div class="px-5 py-4 border-t border-surface-border dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}
                </p>
                @if ($students->hasPages())
                    {{ $students->links() }}
                @endif
            </div>
        @endif
    </div>

    <livewire:students.import-students />
</div>
