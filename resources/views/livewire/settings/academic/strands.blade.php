<div>
    <x-page-header title="Strands" subtitle="Senior High School tracks and strand names" />
    <x-settings-academic-nav />

    <div class="panel mb-6 bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-800">
        <p class="text-sm text-sky-900 dark:text-sky-100">
            Strands apply to <strong>Senior High School</strong> (Grade 11 and Grade 12) only.
            Assign a strand when creating sections, then link students through enrollment.
        </p>
    </div>

    <div class="panel mb-6">
        <select wire:model.live="grade" class="select-field max-w-xs">
            <option value="">All SHS grades</option>
            @foreach ($grades as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <form wire:submit="save" class="panel space-y-3 h-fit">
            <h3 class="font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Strand</h3>

            <div>
                <label class="text-xs font-medium text-slate-500 mb-1 block">Grade Level</label>
                <select wire:model="gradeLevelId" class="select-field">
                    <option value="">Select grade…</option>
                    @foreach ($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('gradeLevelId')" class="mt-1" />
            </div>

            <div>
                <label class="text-xs font-medium text-slate-500 mb-1 block">Strand Name</label>
                <input wire:model="name" type="text" placeholder="e.g. Science, Technology, Engineering and Mathematics" class="input-field">
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <label class="text-xs font-medium text-slate-500 mb-1 block">Strand Code</label>
                <input wire:model="code" type="text" placeholder="e.g. STEM, ABM, HUMSS" class="input-field uppercase">
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary text-sm flex-1">Save Strand</button>
                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="btn-secondary text-sm">Cancel</button>
                @endif
            </div>
        </form>

        <div class="panel lg:col-span-2 overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Code</th>
                        <th>Strand Name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($strands as $strand)
                        <tr>
                            <td>{{ $strand->gradeLevel?->name }}</td>
                            <td class="font-mono text-sm font-semibold">{{ $strand->code }}</td>
                            <td>{{ $strand->name }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button wire:click="edit({{ $strand->id }})" class="text-green-700 text-sm">Edit</button>
                                <button wire:click="delete({{ $strand->id }})" wire:confirm="Delete this strand?" class="text-red-600 text-sm ml-2">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-slate-500">
                                No strands yet. Add STEM, ABM, HUMSS, and other SHS tracks above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
