<div>
    <x-page-header title="Teachers" subtitle="Manage teacher accounts and class assignments">
        <x-slot name="actions">
            @can('create', App\Models\User::class)
                <a href="{{ route('teachers.create') }}" wire:navigate class="btn-primary">Add Teacher</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="panel mb-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Search by name, username, or email..." class="input-field">
            </div>
            <select wire:model.live="status" class="select-field">
                <option value="">All statuses</option>
                <option value="active">Active only</option>
                <option value="inactive">Inactive only</option>
            </select>
        </div>
    </div>

    <div class="panel-flush">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Adviser</th>
                        <th>Schedules</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td>
                                <a href="{{ route('teachers.show', $teacher) }}" wire:navigate
                                    class="font-medium text-green-700 hover:text-brand-500">
                                    {{ $teacher->full_name }}
                                </a>
                            </td>
                            <td class="font-mono text-sm">{{ $teacher->username }}</td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ $teacher->email }}</td>
                            <td class="text-sm">{{ $teacher->advised_sections_count }} section(s)</td>
                            <td class="text-sm">{{ $teacher->class_schedules_count }} class(es)</td>
                            <td>
                                @if ($teacher->is_active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right space-x-3">
                                <a href="{{ route('teachers.show', $teacher) }}" wire:navigate
                                    class="text-sm font-medium text-green-700 hover:text-brand-500">Students</a>
                                @can('update', $teacher)
                                    <button type="button" wire:click="toggleActive({{ $teacher->id }})"
                                        class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                        {{ $teacher->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <p class="text-slate-500 mb-3">No teachers found.</p>
                                @can('create', App\Models\User::class)
                                    <a href="{{ route('teachers.create') }}" wire:navigate class="btn-primary text-sm">Add
                                        your first teacher</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($teachers->hasPages())
            <div class="px-5 py-4 border-t border-surface-border dark:border-slate-800">
                {{ $teachers->links() }}
            </div>
        @endif
    </div>
</div>
