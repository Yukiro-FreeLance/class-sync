@php
    $quickAddTitle = match ($quickAddPanel) {
        'strand' => 'Strand',
        'section' => 'Section',
        'subject' => 'Subject',
        'teacher' => 'Teacher',
        'room' => 'Room',
        default => 'Item',
    };
@endphp

<x-offcanvas :title="'Add '.$quickAddTitle">
    @switch($quickAddPanel)
        @case('strand')
            <form wire:submit="saveQuickStrand" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Grade Level</label>
                    <select wire:model="quickStrandGradeLevelId" class="select-field">
                        <option value="">Select grade…</option>
                        @foreach ($shsGrades as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('quickStrandGradeLevelId')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Strand Name</label>
                    <input wire:model="quickStrandName" type="text" class="input-field" placeholder="e.g. Accountancy, Business and Management">
                    <x-input-error :messages="$errors->get('quickStrandName')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Strand Code</label>
                    <input wire:model="quickStrandCode" type="text" class="input-field uppercase" placeholder="e.g. ABM">
                    <x-input-error :messages="$errors->get('quickStrandCode')" class="mt-1" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm flex-1">Save Strand</button>
                    <button type="button" wire:click="closeQuickAdd" class="btn-secondary text-sm">Cancel</button>
                </div>
            </form>
            @break

        @case('section')
            <form wire:submit="saveQuickSection" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Grade Level</label>
                    <select wire:model.live="quickSectionGradeLevelId" class="select-field">
                        <option value="">Select grade…</option>
                        @foreach ($grades as $g)
                            <option value="{{ $g->id }}">{{ $g->department?->code ? strtoupper($g->department->code).' — ' : '' }}{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('quickSectionGradeLevelId')" class="mt-1" />
                </div>
                @if ($quickSectionIsShs)
                    <div>
                        <label class="text-xs font-medium text-slate-500 mb-1 block">Strand</label>
                        <select wire:model="quickSectionCourseId" class="select-field">
                            <option value="">Select strand…</option>
                            @foreach ($quickSectionStrands as $strandOption)
                                <option value="{{ $strandOption->id }}">{{ $strandOption->code }} — {{ $strandOption->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('quickSectionCourseId')" class="mt-1" />
                    </div>
                @endif
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Section Name</label>
                    <input wire:model="quickSectionName" type="text" class="input-field" placeholder="A, B, Einstein">
                    <x-input-error :messages="$errors->get('quickSectionName')" class="mt-1" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm flex-1">Save Section</button>
                    <button type="button" wire:click="closeQuickAdd" class="btn-secondary text-sm">Cancel</button>
                </div>
            </form>
            @break

        @case('subject')
            <form wire:submit="saveQuickSubject" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Department</label>
                    <select wire:model="quickSubjectDepartmentId" class="select-field">
                        <option value="">All departments (shared)</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('quickSubjectDepartmentId')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Subject Name</label>
                    <input wire:model="quickSubjectName" type="text" class="input-field" placeholder="Mathematics">
                    <x-input-error :messages="$errors->get('quickSubjectName')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Subject Code</label>
                    <input wire:model="quickSubjectCode" type="text" class="input-field uppercase" placeholder="MATH">
                    <x-input-error :messages="$errors->get('quickSubjectCode')" class="mt-1" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm flex-1">Save Subject</button>
                    <button type="button" wire:click="closeQuickAdd" class="btn-secondary text-sm">Cancel</button>
                </div>
            </form>
            @break

        @case('teacher')
            <form wire:submit="saveQuickTeacher" class="space-y-4">
                <p class="text-xs text-slate-500">Creates an active teacher account with a temporary password.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-500 mb-1 block">First Name</label>
                        <input wire:model="quickTeacherFirstName" type="text" class="input-field">
                        <x-input-error :messages="$errors->get('quickTeacherFirstName')" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500 mb-1 block">Last Name</label>
                        <input wire:model="quickTeacherLastName" type="text" class="input-field">
                        <x-input-error :messages="$errors->get('quickTeacherLastName')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Username</label>
                    <input wire:model="quickTeacherUsername" type="text" class="input-field" placeholder="jdoe">
                    <x-input-error :messages="$errors->get('quickTeacherUsername')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Email <span class="text-slate-400">(optional)</span></label>
                    <input wire:model="quickTeacherEmail" type="email" class="input-field" placeholder="teacher@school.edu">
                    <x-input-error :messages="$errors->get('quickTeacherEmail')" class="mt-1" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm flex-1">Save Teacher</button>
                    <button type="button" wire:click="closeQuickAdd" class="btn-secondary text-sm">Cancel</button>
                </div>
            </form>
            @break

        @case('room')
            <form wire:submit="saveQuickRoom" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Room Name</label>
                    <input wire:model="quickRoomName" type="text" class="input-field" placeholder="Room 101">
                    <x-input-error :messages="$errors->get('quickRoomName')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Code <span class="text-slate-400">(optional)</span></label>
                    <input wire:model="quickRoomCode" type="text" class="input-field uppercase" placeholder="R101">
                    <x-input-error :messages="$errors->get('quickRoomCode')" class="mt-1" />
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Building <span class="text-slate-400">(optional)</span></label>
                    <input wire:model="quickRoomBuilding" type="text" class="input-field" placeholder="Main Building">
                    <x-input-error :messages="$errors->get('quickRoomBuilding')" class="mt-1" />
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary text-sm flex-1">Save Room</button>
                    <button type="button" wire:click="closeQuickAdd" class="btn-secondary text-sm">Cancel</button>
                </div>
            </form>
            @break
    @endswitch
</x-offcanvas>
