<?php

namespace App\Livewire\Attendance;

use App\DTOs\Attendance\AttendanceRecordDTO;
use App\Enums\AttendanceMethod;
use App\Livewire\Concerns\HasAttendanceClassFilters;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\Student;
use App\Services\Attendance\AttendancePeriodService;
use App\Services\Students\StudentListService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\ClassScheduleResolver;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Attendance')]
class Index extends Component
{
    use HasAttendanceClassFilters;

    #[Url]
    public string $mode = 'gate';

    public string $search = '';

    public ?int $selectedStudentId = null;

    public string $action = 'in';

    public string $remarks = '';

    public ?int $classRemarkId = null;

    public bool $classWentOut = false;

    public string $classRemarks = '';

    public function mount(AttendancePeriodService $periodService): void
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $this->date = Carbon::today()->toDateString();
        $periodService->ensureDefaults();
        $this->classRemarkId = $periodService->defaultRemarkId();

        if ($this->isTeacherAttendanceScoped()) {
            $this->mode = 'class';
        }
    }

    public function updatedMode(): void
    {
        if ($this->isTeacherAttendanceScoped() && $this->mode === 'gate') {
            $this->mode = 'class';
        }

        $this->reset(['search', 'selectedStudentId', 'remarks', 'classRemarks', 'classWentOut']);
        $this->classRemarkId = app(AttendancePeriodService::class)->defaultRemarkId();

        if ($this->mode === 'class' && $this->section) {
            $this->autoSelectClassSchedule();
        }
    }

    protected function onAttendanceFiltersChanged(): void
    {
        if ($this->mode === 'class' && $this->selectedStudentId) {
            $this->loadClassEntryForStudent();
        }
    }

    public function recordGate(AttendanceService $service): void
    {
        if ($this->isTeacherAttendanceScoped()) {
            abort(403, 'Campus gate attendance is not available for teachers.');
        }

        $this->validate([
            'selectedStudentId' => ['required', 'exists:students,id'],
            'action' => ['required', 'in:in,out'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'date' => ['required', 'date'],
        ]);

        $student = Student::query()->findOrFail($this->selectedStudentId);
        $recordedAt = Carbon::parse($this->date.' '.now()->format('H:i:s'));

        if ($this->action === 'in') {
            $service->record(new AttendanceRecordDTO(
                studentId: $student->id,
                method: AttendanceMethod::Manual,
                recordedBy: auth()->id(),
                date: Carbon::parse($this->date),
                timeIn: $recordedAt->format('H:i:s'),
                remarks: $this->remarks ?: null,
            ));
        } else {
            $service->record(new AttendanceRecordDTO(
                studentId: $student->id,
                method: AttendanceMethod::Manual,
                recordedBy: auth()->id(),
                date: Carbon::parse($this->date),
                timeOut: $recordedAt->format('H:i:s'),
                remarks: $this->remarks ?: null,
            ));
        }

        $this->reset(['remarks']);
        $this->dispatch('toast', message: 'Campus attendance recorded.', type: 'success');
    }

    public function recordClass(AttendancePeriodService $periodService): void
    {
        $this->validate([
            'selectedStudentId' => ['required', 'exists:students,id'],
            'section' => ['required', 'exists:sections,id'],
            'classScheduleId' => ['required', 'exists:class_schedules,id'],
            'date' => ['required', 'date'],
            'classRemarkId' => ['required', 'exists:attendance_remarks,id'],
            'classRemarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertCanManageClassAttendance();

        $allowedStudentIds = $this->teacherScope()->accessibleStudentIds(
            auth()->user(),
            (int) $this->section,
            $this->classScheduleId,
        );

        if (! in_array($this->selectedStudentId, $allowedStudentIds, true)) {
            abort(403, 'This student is not enrolled in your class schedule.');
        }

        $periodService->saveClassLog(
            studentId: $this->selectedStudentId,
            classScheduleId: $this->classScheduleId,
            sectionId: (int) $this->section,
            date: $this->date,
            remarkId: $this->classRemarkId,
            remarks: $this->classRemarks ?: null,
            wentOut: $this->classWentOut,
            userId: auth()->id(),
        );

        $this->reset(['classRemarks', 'classWentOut']);
        $this->classRemarkId = $periodService->defaultRemarkId();
        $this->dispatch('toast', message: 'Class attendance recorded.', type: 'success');
    }

    public function selectStudent(int $id): void
    {
        $this->selectedStudentId = $id;
        $this->search = '';

        if ($this->mode === 'class') {
            $this->loadClassEntryForStudent();
        }
    }

    protected function loadClassEntryForStudent(): void
    {
        if (! $this->selectedStudentId || ! $this->classScheduleId) {
            return;
        }

        $log = app(AttendancePeriodService::class)->logForStudent(
            $this->selectedStudentId,
            $this->classScheduleId,
            $this->date,
        );

        $this->classRemarkId = $log?->attendance_remark_id ?? app(AttendancePeriodService::class)->defaultRemarkId();
        $this->classRemarks = $log?->remarks ?? '';
        $this->classWentOut = (bool) $log?->went_out_at && ! $log?->returned_at;
    }

    protected function searchableStudents()
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return Student::query()
            ->when($this->mode === 'class' && $this->section && $this->classScheduleId, function ($query) {
                $studentIds = app(ClassScheduleResolver::class)
                    ->studentsForSchedule((int) $this->section, $this->classScheduleId)
                    ->pluck('id');

                $query->whereIn('id', $studentIds);
            })
            ->where(function ($query) {
                $query->where('student_number', 'like', "%{$this->search}%")
                    ->orWhere('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('middle_name', 'like', "%{$this->search}%");
            })
            ->tap(fn ($query) => StudentListService::orderByGenderThenName($query))
            ->limit(8)
            ->get();
    }

    protected function viewData(): array
    {
        $selectedStudent = $this->selectedStudentId
            ? Student::query()->find($this->selectedStudentId)
            : null;

        $classLogs = collect();

        if ($this->mode === 'class' && $this->section && $this->classScheduleId) {
            $classLogs = app(AttendancePeriodService::class)
                ->logsForSchedule((int) $this->section, $this->classScheduleId, $this->date)
                ->sortBy([
                    fn ($a, $b) => StudentListService::genderSortOrder($a->student?->gender) <=> StudentListService::genderSortOrder($b->student?->gender),
                    fn ($a, $b) => mb_strtolower($a->student?->last_name ?? '') <=> mb_strtolower($b->student?->last_name ?? ''),
                    fn ($a, $b) => mb_strtolower($a->student?->first_name ?? '') <=> mb_strtolower($b->student?->first_name ?? ''),
                    fn ($a, $b) => mb_strtolower((string) $a->student?->middle_name) <=> mb_strtolower((string) $b->student?->middle_name),
                ])
                ->values();
        }

        $todayRecords = $this->isTeacherAttendanceScoped()
            ? collect()
            : app(AttendanceService::class)->getAttendanceForDate(Carbon::parse($this->date));

        return array_merge($this->attendanceClassFilterData(), [
            'students' => $this->searchableStudents(),
            'selectedStudent' => $selectedStudent,
            'todayRecords' => $todayRecords,
            'classLogs' => $classLogs,
            'attendanceRemarks' => AttendanceRemark::query()->active()->ordered()->get(),
        ]);
    }

    public function render()
    {
        return view('livewire.attendance.index', $this->viewData());
    }
}
