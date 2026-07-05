<div>
    <x-page-header title="Sections" subtitle="Class sections by grade, strand, adviser, and room" />
    <x-settings-academic-nav />

    <div class="panel mb-6">
        <div class="grid sm:grid-cols-2 gap-4">
            <select wire:model.live="department" class="select-field">
                <option value="">All departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="grade" class="select-field">
                <option value="">All grades</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <form wire:submit="save" class="panel space-y-3 h-fit">
            <h3 class="font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Section</h3>
            <select wire:model.live="gradeLevelId" class="select-field">
                <option value="">Grade level</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}">{{ $g->department?->code ? strtoupper($g->department->code).' — ' : '' }}{{ $g->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gradeLevelId')" class="mt-1" />

            @if ($showStrandField)
                <div>
                    <label class="text-xs font-medium text-slate-500 mb-1 block">Strand Name</label>
                    <select wire:model="courseId" class="select-field">
                        <option value="">Select strand…</option>
                        @foreach ($strands as $strand)
                            <option value="{{ $strand->id }}">{{ $strand->code }} — {{ $strand->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('courseId')" class="mt-1" />
                    @if ($strands->isEmpty())
                        <p class="text-xs text-amber-600 mt-1">
                            No strands for this grade.
                            <a href="{{ route('settings.academic.strands') }}" wire:navigate class="underline">Add strands</a> first.
                        </p>
                    @endif
                </div>
            @endif

            <select wire:model="academicYearId" class="select-field">
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
            <input wire:model="name" type="text" placeholder="Section name (A, B, Einstein)" class="input-field">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
            <select wire:model="adviserId" class="select-field">
                <option value="">Class adviser (optional)</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                @endforeach
            </select>
            <select wire:model="roomId" class="select-field">
                <option value="">Room (from list)</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->display_name }}</option>
                @endforeach
            </select>
            <input wire:model="room" type="text" placeholder="Room label override (optional)" class="input-field">
            <button type="submit" class="btn-primary text-sm w-full">Save Section</button>
        </form>

        <div class="panel lg:col-span-2 overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Strand</th>
                        <th>Section</th>
                        <th>Year</th>
                        <th>Adviser</th>
                        <th>Room</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sections as $section)
                        <tr>
                            <td>{{ $section->gradeLevel?->name }}</td>
                            <td>
                                @if ($section->course)
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-200">
                                        {{ $section->course->code }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="font-medium">{{ $section->display_label }}</td>
                            <td>{{ $section->academicYear?->name ?? '—' }}</td>
                            <td>{{ $section->adviser?->full_name ?? '—' }}</td>
                            <td>{{ $section->assignedRoom?->display_name ?? $section->room ?? '—' }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button wire:click="edit({{ $section->id }})" class="text-green-700 text-sm">Edit</button>
                                <button wire:click="delete({{ $section->id }})" wire:confirm="Delete section?" class="text-red-600 text-sm ml-2">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-500">No sections found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
