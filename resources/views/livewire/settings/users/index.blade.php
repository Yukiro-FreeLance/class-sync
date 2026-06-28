<div>
    <x-page-header title="Users & Access" subtitle="Manage accounts, roles, and permission restrictions">
        <x-slot name="actions">
            <a href="{{ route('settings.users.create') }}" wire:navigate class="btn-primary">Add User</a>
        </x-slot>
    </x-page-header>

    <x-settings-users-nav />

    <div class="panel-flush mb-6">
        <div class="px-5 py-4 flex flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search users..."
                class="input-field flex-1 min-w-[12rem]">
            <select wire:model.live="role" class="select-field min-w-[10rem]">
                <option value="">All roles</option>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
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
                        <th>Roles</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $user->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="font-mono text-sm">{{ $user->username }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <span class="badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ ucfirst($role->name) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-sm">
                                @if ($user->canActAsTeacher())
                                    <span class="text-emerald-600 dark:text-emerald-400">Yes</span>
                                    @if ($user->acts_as_teacher && $user->hasRole('administrator'))
                                        <span class="text-xs text-slate-400 block">Admin as teacher</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Active</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right space-x-3">
                                @can('update', $user)
                                    <a href="{{ route('settings.users.edit', $user) }}" wire:navigate class="text-green-700 hover:text-brand-500 text-sm font-medium">Edit</a>
                                    @if ($user->id !== auth()->id())
                                        <button type="button" wire:click="toggleActive({{ $user->id }})" class="text-slate-500 hover:text-slate-700 text-sm font-medium">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="px-5 py-4 border-t border-surface-border dark:border-slate-800">{{ $users->links() }}</div>
        @endif
    </div>
</div>
