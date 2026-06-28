<div>
    <x-page-header title="Academic Years" subtitle="Manage school years and set the current year" />
    <x-settings-academic-nav />

    <div class="grid lg:grid-cols-3 gap-6">
        <form wire:submit="save" class="panel lg:col-span-1 space-y-4 h-fit">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Edit' : 'Add' }} Academic Year</h3>
            <input wire:model="name" type="text" placeholder="2025-2026" class="input-field">
            <input wire:model="startDate" type="date" class="input-field">
            <input wire:model="endDate" type="date" class="input-field">
            <label class="flex items-center gap-2 text-sm"><input wire:model="isCurrent" type="checkbox" class="rounded text-green-700"> Set as current year</label>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary text-sm">Save</button>
                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="btn-secondary text-sm">Cancel</button>
                @endif
            </div>
        </form>

        <div class="panel lg:col-span-2">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $year)
                        <tr>
                            <td class="font-medium">{{ $year->name }}</td>
                            <td>{{ $year->start_date->format('M j, Y') }} – {{ $year->end_date->format('M j, Y') }}</td>
                            <td>
                                @if ($year->is_current)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Current</span>
                                @else
                                    <button wire:click="setCurrent({{ $year->id }})" class="text-xs text-green-700 font-medium">Set current</button>
                                @endif
                            </td>
                            <td class="text-right space-x-2">
                                <button wire:click="edit({{ $year->id }})" class="text-green-700 text-sm font-medium">Edit</button>
                                <button wire:click="delete({{ $year->id }})" wire:confirm="Delete this academic year?" class="text-red-600 text-sm font-medium">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-slate-500">No academic years yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
