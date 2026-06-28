<div>
    <x-page-header title="Rooms" subtitle="Classrooms and learning spaces" />
    <x-settings-academic-nav />

    <div class="grid lg:grid-cols-3 gap-6">
        <form wire:submit="save" class="panel space-y-3 h-fit">
            <h3 class="font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Room</h3>
            <input wire:model="name" type="text" placeholder="Room name" class="input-field">
            <input wire:model="code" type="text" placeholder="Code (optional)" class="input-field">
            <input wire:model="building" type="text" placeholder="Building" class="input-field">
            <input wire:model="capacity" type="number" min="1" placeholder="Capacity" class="input-field">
            <label class="flex items-center gap-2 text-sm"><input wire:model="isActive" type="checkbox" class="rounded text-green-700"> Active</label>
            <button type="submit" class="btn-primary text-sm w-full">Save Room</button>
        </form>

        <div class="panel lg:col-span-2 overflow-x-auto">
            <table class="w-full data-table">
                <thead><tr><th>Room</th><th>Building</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td class="font-medium">{{ $room->name }} @if($room->code)<span class="text-slate-400">({{ $room->code }})</span>@endif</td>
                            <td>{{ $room->building ?? '—' }}</td>
                            <td>{{ $room->capacity ?? '—' }}</td>
                            <td>{{ $room->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-right">
                                <button wire:click="edit({{ $room->id }})" class="text-green-700 text-sm">Edit</button>
                                <button wire:click="delete({{ $room->id }})" wire:confirm="Delete room?" class="text-red-600 text-sm ml-2">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-8 text-slate-500">No rooms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
