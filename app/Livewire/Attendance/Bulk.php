<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\HasAttendanceClassFilters;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\Section;
use App\Models\Student;
use App\Services\Attendance\AttendancePeriodService;
use App\Services\Attendance\ClassScheduleResolver;
use App\Services\Students\StudentListService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Bulk Class Attendance')]
class Bulk extends Component
{
    use HasAttendanceClassFilters;

    /** @var array<int, array{remark_id: int, remarks: string, went_out: bool}> */
    public array $entries = [];

    public string $studentSearch = '';

    public string $statusFilter = '';

    public string $genderFilter = '';

    public function mount(AttendancePeriodService $periodService): void
    {
        $this->authorize('create', AttendanceRecord::class);
        $periodService->ensureDefaults();
        $this->date = Carbon::today()->toDateString();
        $this->autoPrefillDepartment();

        if ($this->section) {
            $this->autoSelectClassSchedule();
            $this->loadStudents();
        }
    }

    protected function onAttendanceFiltersChanged(): void
    {
        $this->loadStudents();
    }

    public function loadStudents(): void
    {
        if (! $this->section || ! $this->classScheduleId) {
            $this->entries = [];

            return;
        }

        $periodService = app(AttendancePeriodService::class);
        $defaultRemarkId = $periodService->defaultRemarkId();
        $existing = $periodService->logsForSchedule(
            (int) $this->section,
            $this->classScheduleId,
            $this->date,
        );

        $students = app(ClassScheduleResolver::class)
            ->studentsForSchedule((int) $this->section, $this->classScheduleId);

        $this->entries = [];

        foreach ($students as $student) {
            $log = $existing->get($student->id);
            $this->entries[$student->id] = [
                'remark_id' => $log?->attendance_remark_id ?? $defaultRemarkId,
                'remarks' => $log?->remarks ?? '',
                'went_out' => (bool) $log?->went_out_at && ! $log?->returned_at,
            ];
        }
    }

    public function markAll(int $remarkId): void
    {
        foreach ($this->entries as $studentId => $entry) {
            $this->entries[$studentId]['remark_id'] = $remarkId;
        }
    }

    public function setStudentRemark(int $studentId, int $remarkId): void
    {
        if (! isset($this->entries[$studentId])) {
            return;
        }

        $this->entries[$studentId]['remark_id'] = $remarkId;
    }

    /**
     * @return array<int, array{remark: AttendanceRemark, count: int}>
     */
    protected function attendanceSummary(iterable $remarks): array
    {
        $summary = [];

        foreach ($remarks as $remark) {
            $summary[$remark->id] = ['remark' => $remark, 'count' => 0];
        }

        foreach ($this->entries as $entry) {
            $remarkId = (int) $entry['remark_id'];

            if (isset($summary[$remarkId])) {
                $summary[$remarkId]['count']++;
            }
        }

        return $summary;
    }

    /**
     * @return array{present: int, absent: int, rate: int}
     */
    protected function attendanceStats(iterable $remarks): array
    {
        $present = 0;
        $absent = 0;
        $remarkMap = collect($remarks)->keyBy('id');

        foreach ($this->entries as $entry) {
            $remark = $remarkMap->get((int) $entry['remark_id']);

            if ($remark?->counts_as_present) {
                $present++;
            } else {
                $absent++;
            }
        }

        $total = count($this->entries);
        $rate = $total > 0 ? (int) round(($present / $total) * 100) : 0;

        return compact('present', 'absent', 'rate');
    }

    public function save(AttendancePeriodService $periodService): void
    {
        $this->validate([
            'section' => ['required', 'exists:sections,id'],
            'classScheduleId' => ['required', 'exists:class_schedules,id'],
            'date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
        ]);

        $this->assertCanManageClassAttendance();

        $payload = [];

        foreach ($this->entries as $studentId => $entry) {
            $payload[$studentId] = [
                'remark_id' => (int) $entry['remark_id'],
                'remarks' => $entry['remarks'] ?: null,
                'went_out' => (bool) ($entry['went_out'] ?? false),
            ];
        }

        $result = $periodService->bulkSave(
            (int) $this->section,
            (int) $this->classScheduleId,
            $this->date,
            $payload,
            auth()->id(),
        );

        $message = "Saved attendance for {$result['saved']} student(s).";

        if ($result['errors'] !== []) {
            $message .= ' '.implode(' ', $result['errors']);
        }

        $this->dispatch('toast', message: $message, type: $result['errors'] === [] ? 'success' : 'warning');
        $this->loadStudents();
    }

    public function render()
    {
        $filterData = $this->attendanceClassFilterData();
        $remarks = AttendanceRemark::query()->active()->ordered()->get();
        $students = ($this->section && $this->classScheduleId)
            ? app(ClassScheduleResolver::class)->studentsForSchedule((int) $this->section, $this->classScheduleId)
            : collect();

        if ($this->studentSearch !== '') {
            $needle = mb_strtolower($this->studentSearch);
            $students = $students->filter(function (Student $student) use ($needle) {
                return str_contains(mb_strtolower($student->last_name), $needle)
                    || str_contains(mb_strtolower($student->first_name), $needle)
                    || str_contains(mb_strtolower((string) $student->middle_name), $needle)
                    || str_contains(mb_strtolower($student->student_number), $needle);
            });
        }

        if ($this->statusFilter !== '') {
            $filterId = (int) $this->statusFilter;
            $students = $students->filter(
                fn (Student $student) => (int) ($this->entries[$student->id]['remark_id'] ?? 0) === $filterId,
            );
        }

        if ($this->genderFilter !== '') {
            $students = StudentListService::filterCollectionByGender($students, $this->genderFilter);
        }

        $students = StudentListService::sortByGenderThenName($students);

        $selectedSection = $this->section
            ? Section::query()->with(['gradeLevel.department', 'course', 'academicYear', 'adviser'])->find($this->section)
            : null;

        return view('livewire.attendance.bulk', array_merge($filterData, [
            'remarks' => $remarks,
            'students' => $students,
            'studentGenderGroups' => StudentListService::groupByGender($students),
            'selectedSection' => $selectedSection,
            'attendanceSummary' => $this->attendanceSummary($remarks),
            'attendanceStats' => $this->attendanceStats($remarks),
            'totalStudents' => count($this->entries),
            'filteredCount' => $students->count(),
            'genderFilters' => StudentListService::genderFilterOptions(),
        ]));
    }
}
