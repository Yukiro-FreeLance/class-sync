<div>
    <div class="mb-6">
        <a href="{{ route('students.index') }}" wire:navigate
            class="text-sm text-green-700 hover:text-brand-500 font-medium">&larr; Back to Students</a>
        <h1 class="page-title mt-2">QR Code Generator</h1>
        <p class="page-subtitle">Select students to generate printable QR codes</p>
    </div>

    <div class="panel mb-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search students..."
                class="input-field flex-1">
            <select wire:model.live="gender" class="select-field">
                @foreach ($genderFilters as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button wire:click="selectAll" type="button" class="btn-secondary">Select All</button>
                <button wire:click="clearSelection" type="button" class="btn-secondary">Clear</button>
                @if (count($selectedIds) > 0)
                    <button onclick="window.print()" type="button" class="btn-primary print:hidden">Print
                        Selected</button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Students ({{ count($selectedIds) }}
                selected)</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @php $genderGroups = \App\Services\Students\StudentListService::groupByGender($students); @endphp
                @foreach ($genderGroups as $genderKey => $genderStudents)
                    <x-student-gender-divider :gender-key="$genderKey" :count="$genderStudents->count()" :groups="$genderGroups" class="rounded-lg" />
                    @foreach ($genderStudents as $student)
                    <label
                        class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                        <input type="checkbox" wire:click="toggleStudent({{ $student->id }})"
                            @checked(in_array($student->id, $selectedIds))
                            class="rounded border-surface-border text-green-700 focus:ring-brand-500">
                        <div>
                            <p class="font-medium text-sm">{{ $student->list_name }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $student->student_number }}</p>
                        </div>
                    </label>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="panel print:shadow-none print:border-0">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4 print:hidden">Preview</h3>
            @if (count($selectedIds) > 0)
                <div class="grid grid-cols-2 gap-4">
                    @foreach ($students->whereIn('id', $selectedIds) as $student)
                        <div
                            class="text-center p-4 border border-surface-border dark:border-slate-700 rounded-xl break-inside-avoid">
                            @if (isset($qrCodes[$student->id]))
                                <img src="data:image/png;base64,{{ $qrCodes[$student->id] }}" alt="QR Code"
                                    class="mx-auto w-32 h-32">
                            @endif
                            <p class="font-medium text-sm mt-2">{{ $student->list_name }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $student->student_number }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-slate-500 text-sm text-center py-12">Select students to preview QR codes</p>
            @endif
        </div>
    </div>
</div>
