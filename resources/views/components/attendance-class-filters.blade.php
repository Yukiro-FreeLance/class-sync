@props([

    'departments',

    'grades',

    'sections',

    'classSchedules',

    'selectedSchedule' => null,

    'weekdayLabel' => null,

    'showSchedule' => true,

    'departmentId' => '',

    'gradeId' => '',

    'sectionId' => '',

    'labeled' => false,

    'compactHint' => true,

])



<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">

    <div>

        @if ($labeled)

            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Department</label>

        @endif

        <select wire:model.live="department" class="select-field">

            <option value="">Department</option>

            @foreach ($departments as $dept)

                <option value="{{ $dept->id }}">{{ $dept->name }}</option>

            @endforeach

        </select>

    </div>



    <div>

        @if ($labeled)

            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Grade</label>

        @endif

        <select wire:model.live="grade" class="select-field" {{ $departmentId ? '' : 'disabled' }}>

            <option value="">Grade</option>

            @foreach ($grades as $gradeLevel)

                <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>

            @endforeach

        </select>

    </div>



    <div>

        @if ($labeled)

            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Section</label>

        @endif

        <select wire:model.live="section" class="select-field" {{ $gradeId ? '' : 'disabled' }}>

            <option value="">Section</option>

            @foreach ($sections as $section)

                <option value="{{ $section->id }}">{{ $section->name }}</option>

            @endforeach

        </select>

    </div>



    <div>

        @if ($labeled)

            <label class="text-[11px] font-medium text-slate-500 mb-1 block">Date</label>

        @endif

        <input wire:model.live="date" type="date" class="input-field">

    </div>



    @if ($showSchedule)

        <div>

            @if ($labeled)

                <label class="text-[11px] font-medium text-slate-500 mb-1 block">Class / Subject</label>

            @endif

            <select wire:model.live="classScheduleId" class="select-field" {{ $sectionId ? '' : 'disabled' }}>

                <option value="">Class / Subject</option>

                @foreach ($classSchedules as $schedule)

                    <option value="{{ $schedule->id }}">{{ $schedule->display_label }}</option>

                @endforeach

            </select>

        </div>

    @endif

</div>



@if ($compactHint && $showSchedule && $sectionId && ! $selectedSchedule)

    <div class="mt-3 text-xs text-slate-500">

        @if ($weekdayLabel)

            Classes scheduled for <span class="font-medium text-slate-700 dark:text-slate-300">{{ $weekdayLabel }}</span>.

        @endif

        @if ($classSchedules->isEmpty())

            <span class="text-amber-600 dark:text-amber-400">No subjects scheduled for this section on the selected day.</span>

        @endif

    </div>

@endif

