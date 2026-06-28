<div>
    <x-page-header title="Subjects" subtitle="Curriculum subjects by department" />
    <x-settings-academic-nav />

    <div class="panel mb-6">
        <select wire:model.live="department" class="select-field max-w-xs">
            <option value="">All departments</option>
            @foreach ($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <form wire:submit="save" class="panel space-y-3 h-fit">
            <h3 class="font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Subject</h3>
            <select wire:model="departmentId" class="select-field">
                <option value="">All departments (shared)</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <input wire:model="name" type="text" placeholder="Subject name" class="input-field">
            <input wire:model="code" type="text" placeholder="Code (MATH, ENG)" class="input-field">
            <textarea wire:model="description" rows="2" placeholder="Description" class="input-field"></textarea>
            <label class="flex items-center gap-2 text-sm"><input wire:model="isActive" type="checkbox" class="rounded text-green-700"> Active</label>
            <button type="submit" class="btn-primary text-sm w-full">Save Subject</button>
        </form>

        <div class="panel lg:col-span-2 overflow-x-auto">
            <table class="w-full data-table">
                <thead><tr><th>Code</th><th>Subject</th><th>Department</th><th></th></tr></thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr>
                            <td class="font-mono text-sm">{{ $subject->code }}</td>
                            <td>{{ $subject->name }}</td>
                            <td>{{ $subject->department?->name ?? 'All' }}</td>
                            <td class="text-right">
                                <button wire:click="edit({{ $subject->id }})" class="text-green-700 text-sm">Edit</button>
                                <button wire:click="delete({{ $subject->id }})" wire:confirm="Delete subject?" class="text-red-600 text-sm ml-2">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-slate-500">No subjects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
