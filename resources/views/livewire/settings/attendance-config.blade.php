<div>
    <x-page-header title="Attendance Configuration" subtitle="Remarks, statuses, and class periods" />

    <div class="flex gap-2 mb-6">
        <button type="button" wire:click="$set('tab', 'remarks')" @class(['px-4 py-2 rounded-lg text-sm font-medium', 'bg-green-700 text-white' => $tab === 'remarks', 'btn-secondary' => $tab !== 'remarks'])>Remarks</button>
        <button type="button" wire:click="$set('tab', 'periods')" @class(['px-4 py-2 rounded-lg text-sm font-medium', 'bg-green-700 text-white' => $tab === 'periods', 'btn-secondary' => $tab !== 'periods'])>Class Periods</button>
    </div>

    @if ($tab === 'remarks')
        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="saveRemark" class="panel space-y-3 h-fit">
                <h3 class="font-semibold">{{ $editingRemarkId ? 'Edit' : 'Add' }} Remark</h3>
                <input wire:model="remarkLabel" type="text" placeholder="Label (e.g. Present)" class="input-field">
                <input wire:model="remarkCode" type="text" placeholder="Code (present)" class="input-field">
                <input wire:model="remarkColor" type="color" class="h-10 w-full rounded-lg border border-surface-border">
                <input wire:model="remarkSortOrder" type="number" min="0" class="input-field" placeholder="Sort order">
                <label class="flex items-center gap-2 text-sm"><input wire:model="remarkCountsAsPresent" type="checkbox" class="rounded text-green-700"> Counts as present</label>
                <label class="flex items-center gap-2 text-sm"><input wire:model="remarkIsDefault" type="checkbox" class="rounded text-green-700"> Default for new entries</label>
                <label class="flex items-center gap-2 text-sm"><input wire:model="remarkIsActive" type="checkbox" class="rounded text-green-700"> Active</label>
                <button type="submit" class="btn-primary text-sm w-full">Save Remark</button>
            </form>
            <div class="panel lg:col-span-2 overflow-x-auto">
                <table class="w-full data-table">
                    <thead><tr><th>Label</th><th>Code</th><th>Present?</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($remarks as $remark)
                            <tr>
                                <td><span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background:{{ $remark->color }}"></span>{{ $remark->label }}</span></td>
                                <td class="font-mono text-sm">{{ $remark->code }}</td>
                                <td>{{ $remark->counts_as_present ? 'Yes' : 'No' }}</td>
                                <td class="text-right">
                                    <button wire:click="editRemark({{ $remark->id }})" class="text-green-700 text-sm">Edit</button>
                                    <button wire:click="deleteRemark({{ $remark->id }})" wire:confirm="Delete?" class="text-red-600 text-sm ml-2">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-6">
            <form wire:submit="savePeriod" class="panel space-y-3 h-fit">
                <h3 class="font-semibold">{{ $editingPeriodId ? 'Edit' : 'Add' }} Period</h3>
                <input wire:model="periodName" type="text" placeholder="Period 1" class="input-field">
                <input wire:model="periodCode" type="text" placeholder="p1" class="input-field">
                <div class="grid grid-cols-2 gap-2">
                    <input wire:model="periodStartsAt" type="time" class="input-field">
                    <input wire:model="periodEndsAt" type="time" class="input-field">
                </div>
                <input wire:model="periodSortOrder" type="number" min="0" class="input-field">
                <label class="flex items-center gap-2 text-sm"><input wire:model="periodIsActive" type="checkbox" class="rounded text-green-700"> Active</label>
                <button type="submit" class="btn-primary text-sm w-full">Save Period</button>
            </form>
            <div class="panel lg:col-span-2 overflow-x-auto">
                <table class="w-full data-table">
                    <thead><tr><th>Period</th><th>Time</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($periods as $period)
                            <tr>
                                <td class="font-medium">{{ $period->name }}</td>
                                <td>{{ $period->time_range ?? '—' }}</td>
                                <td class="text-right">
                                    <button wire:click="editPeriod({{ $period->id }})" class="text-green-700 text-sm">Edit</button>
                                    <button wire:click="deletePeriod({{ $period->id }})" wire:confirm="Delete?" class="text-red-600 text-sm ml-2">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
