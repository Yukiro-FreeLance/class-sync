<div>
    <x-page-header title="Academic Configuration" subtitle="Departments and grade levels (Elem, JHS, SHS)" />
    <x-settings-academic-nav />

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Departments</h3>
            <form wire:submit="saveDepartment" class="space-y-3 mb-6">
                <input wire:model="departmentName" type="text" placeholder="Name (e.g. Junior High School)" class="input-field">
                <input wire:model="departmentCode" type="text" placeholder="Code (elem, jhs, shs)" class="input-field">
                <div class="flex gap-3">
                    <input wire:model="departmentSortOrder" type="number" min="0" class="input-field w-24" placeholder="Order">
                    <label class="flex items-center gap-2 text-sm"><input wire:model="departmentIsActive" type="checkbox" class="rounded text-green-700"> Active</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-sm">{{ $editingDepartmentId ? 'Update' : 'Add' }} Department</button>
                    @if ($editingDepartmentId)
                        <button type="button" wire:click="resetDepartmentForm" class="btn-secondary text-sm">Cancel</button>
                    @endif
                </div>
            </form>

            <div class="space-y-2">
                @foreach ($departments as $department)
                    <div class="rounded-lg border border-surface-border dark:border-slate-700 px-3 py-2 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="font-medium">{{ $department->name }}</span>
                                <span class="text-slate-500 ml-2">({{ strtoupper($department->code) }}) · {{ $department->grade_levels_count }} grades</span>
                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                    @forelse ($department->semesterEntries() as $entry)
                                        <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] text-slate-600 dark:text-slate-300">
                                            {{ $entry['label'] }}
                                        </span>
                                    @empty
                                        <span class="text-[11px] text-slate-400">No semesters configured</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <button wire:click="configureSemesters({{ $department->id }})" class="text-xs font-medium text-green-700 hover:text-brand-600">
                                    Configure Semesters
                                </button>
                                <div class="flex gap-2">
                                    <button wire:click="editDepartment({{ $department->id }})" class="text-green-700 text-xs font-medium">Edit</button>
                                    <button wire:click="deleteDepartment({{ $department->id }})" wire:confirm="Delete this department?" class="text-red-600 text-xs font-medium">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Grade Levels</h3>
            <form wire:submit="saveGrade" class="space-y-3 mb-6">
                <select wire:model="gradeDepartmentId" class="select-field">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <input wire:model="gradeName" type="text" placeholder="Grade name" class="input-field">
                <div class="flex gap-3">
                    <input wire:model="gradeCode" type="text" placeholder="Code" class="input-field flex-1">
                    <input wire:model="gradeSortOrder" type="number" min="0" class="input-field w-24" placeholder="Order">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-sm">{{ $editingGradeId ? 'Update' : 'Add' }} Grade</button>
                    @if ($editingGradeId)
                        <button type="button" wire:click="resetGradeForm" class="btn-secondary text-sm">Cancel</button>
                    @endif
                </div>
            </form>
            <div class="max-h-96 overflow-y-auto space-y-2">
                @foreach ($grades->groupBy(fn ($g) => $g->department?->name ?? 'Unassigned') as $deptName => $group)
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-3 first:mt-0">{{ $deptName }}</p>
                    @foreach ($group as $grade)
                        <div class="flex items-center justify-between rounded-lg border border-surface-border dark:border-slate-700 px-3 py-2 text-sm">
                            <span>{{ $grade->name }} <span class="text-slate-400">({{ $grade->code }})</span></span>
                            <div class="flex gap-2">
                                <button wire:click="editGrade({{ $grade->id }})" class="text-green-700 text-xs font-medium">Edit</button>
                                <button wire:click="deleteGrade({{ $grade->id }})" wire:confirm="Delete this grade level?" class="text-red-600 text-xs font-medium">Delete</button>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    {{-- Configure Semesters modal --}}
    @if ($configuringDepartment)
        <div
            x-data="{ show: @entangle('showSemesterModal').live }"
            x-show="show"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-8 sm:px-6"
            @keydown.escape.window="$wire.cancelSemesterConfig()"
        >
            <div
                x-show="show"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                wire:click="cancelSemesterConfig"
            ></div>

            <div class="relative flex min-h-full items-center justify-center">
                <div
                    x-show="show"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                    class="relative w-full max-w-lg panel shadow-2xl"
                    @click.stop
                >
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">Configure Semesters</p>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-0.5">{{ $configuringDepartment->name }}</h3>
                            <p class="text-xs text-slate-500 mt-1">Enable semesters and set display names for schedules and enrollment.</p>
                        </div>
                        <button type="button" wire:click="cancelSemesterConfig" class="btn-ghost p-2 -mr-2 -mt-1 rounded-lg" aria-label="Close">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveSemesters" class="space-y-4">
                        <div class="rounded-xl border border-surface-border dark:border-slate-800 overflow-hidden">
                            <div class="grid grid-cols-[auto_1fr_auto] gap-3 px-4 py-2 bg-slate-50 dark:bg-slate-900/50 border-b border-surface-border dark:border-slate-800 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <span>Use</span>
                                <span>Display name</span>
                                <span>Code</span>
                            </div>
                            <div class="divide-y divide-surface-border dark:divide-slate-800">
                                @foreach ($semesterDefinitions as $semester)
                                    @php $config = $departmentSemesterConfig[$semester->value] ?? ['enabled' => false, 'label' => $semester->label()]; @endphp
                                    <div wire:key="configure-semester-{{ $semester->value }}"
                                        @class([
                                            'grid grid-cols-[auto_1fr_auto] gap-3 items-center px-4 py-3 transition',
                                            'bg-brand-50/50 dark:bg-brand-900/10' => $config['enabled'] ?? false,
                                        ])>
                                        <input type="checkbox"
                                            wire:model.live="departmentSemesterConfig.{{ $semester->value }}.enabled"
                                            class="rounded text-brand-600">
                                        <input type="text"
                                            wire:model="departmentSemesterConfig.{{ $semester->value }}.label"
                                            placeholder="{{ $semester->label() }}"
                                            @disabled(! ($config['enabled'] ?? false))
                                            @class([
                                                'input-field text-sm py-2',
                                                'opacity-50 cursor-not-allowed' => ! ($config['enabled'] ?? false),
                                            ])>
                                        <span class="text-xs font-mono text-slate-400">{{ $semester->value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <x-input-error :messages="$errors->get('departmentSemesterConfig')" />
                        @foreach ($semesterDefinitions as $semester)
                            <x-input-error :messages="$errors->get('departmentSemesterConfig.'.$semester->value.'.label')" />
                        @endforeach

                        <div class="flex flex-wrap items-center justify-end gap-2 pt-1 border-t border-surface-border dark:border-slate-800">
                            <button type="button" wire:click="cancelSemesterConfig" class="btn-secondary text-sm">Cancel</button>
                            <button type="button" wire:click="resetSemesterLabels" class="btn-secondary text-sm">Reset labels</button>
                            <button type="submit" class="btn-primary text-sm">Save Semesters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
