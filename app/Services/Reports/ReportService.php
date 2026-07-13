<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportPreview;
use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\Student;
use App\Models\Visitor;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    /**
     * @return array<string, string>
     */
    public function reportTypes(): array
    {
        return [
            'attendance_summary' => 'Attendance Summary',
            'daily_attendance' => 'Daily Attendance',
            'student_list' => 'Student List',
            'late_arrivals' => 'Late Arrivals',
            'absent_students' => 'Absent Students',
            'visitor_log' => 'Visitor Log',
        ];
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    public function preview(string $type, string $dateFrom, string $dateTo, array $filters = []): ReportPreview
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();
        $periodLabel = $from->format('M j, Y').' – '.$to->format('M j, Y');

        return match ($type) {
            'attendance_summary' => $this->attendanceSummary($from, $to, $periodLabel, $filters),
            'daily_attendance' => $this->dailyAttendance($from, $to, $periodLabel, $filters),
            'student_list' => $this->studentList('Active students · '.now()->format('M j, Y'), $filters),
            'late_arrivals' => $this->lateArrivals($from, $to, $periodLabel, $filters),
            'absent_students' => $this->absentStudents($from, $to, $periodLabel, $filters),
            'visitor_log' => $this->visitorLog($from, $to, $periodLabel),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    protected function attendanceSummary(Carbon $from, Carbon $to, string $periodLabel, array $filters): ReportPreview
    {
        $gateRecords = $this->gateQuery($from, $to, $filters)->get();
        $classLogs = $this->classLogQuery($from, $to, $filters)->with(['remark', 'student.section', 'student.gradeLevel', 'classSchedule.subject', 'section.gradeLevel'])->get();

        $gateByStatus = $gateRecords->groupBy(fn ($r) => $r->status?->value ?? 'unknown')->map->count();
        $classPresent = $classLogs->filter(fn ($log) => $log->remark?->counts_as_present)->count();
        $classTotal = $classLogs->count();
        $presentRate = $classTotal > 0 ? round(($classPresent / $classTotal) * 100, 1) : 0;

        $summaryStats = [
            ['label' => 'Gate records', 'value' => $gateRecords->count()],
            ['label' => 'Present (gate)', 'value' => (int) ($gateByStatus[AttendanceStatus::Present->value] ?? 0)],
            ['label' => 'Late (gate)', 'value' => (int) ($gateByStatus[AttendanceStatus::Late->value] ?? 0)],
            ['label' => 'Class records', 'value' => $classTotal],
            ['label' => 'Class present rate', 'value' => $classTotal > 0 ? "{$presentRate}%" : '—', 'hint' => 'Based on remark settings'],
            ['label' => 'Unique students', 'value' => $gateRecords->pluck('student_id')->merge($classLogs->pluck('student_id'))->unique()->count()],
        ];

        $remarkRows = AttendanceRemark::query()->active()->ordered()->get()->map(function (AttendanceRemark $remark) use ($classLogs) {
            $count = $classLogs->where('attendance_remark_id', $remark->id)->count();

            return [
                'remark' => $remark->label,
                'counts_as_present' => $remark->counts_as_present ? 'Yes' : 'No',
                'count' => $count,
                'share' => $classLogs->count() > 0 ? round(($count / $classLogs->count()) * 100, 1).'%' : '0%',
            ];
        })->filter(fn ($row) => $row['count'] > 0)->values()->all();

        $sectionRows = $classLogs->groupBy('section_id')->map(function (Collection $logs, $sectionId) {
            $section = $logs->first()?->section;
            $present = $logs->filter(fn ($log) => $log->remark?->counts_as_present)->count();
            $total = $logs->count();

            return [
                'section' => $section ? ($section->gradeLevel?->name.' — '.$section->name) : 'Unknown',
                'records' => $total,
                'present' => $present,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1).'%' : '—',
            ];
        })->sortByDesc('records')->values()->all();

        $dailyRows = $this->dailyBreakdownRows($from, $to, $filters);

        return new ReportPreview(
            title: 'Attendance Summary',
            periodLabel: $periodLabel,
            summaryStats: $summaryStats,
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'gate_records', 'label' => 'Gate', 'align' => 'center'],
                ['key' => 'present', 'label' => 'Present', 'align' => 'center'],
                ['key' => 'late', 'label' => 'Late', 'align' => 'center'],
                ['key' => 'class_records', 'label' => 'Class', 'align' => 'center'],
                ['key' => 'class_present_rate', 'label' => 'Class rate', 'align' => 'center'],
            ],
            rows: $dailyRows,
            tables: array_filter([
                $remarkRows !== [] ? [
                    'title' => 'Class attendance by remark',
                    'columns' => [
                        ['key' => 'remark', 'label' => 'Remark'],
                        ['key' => 'counts_as_present', 'label' => 'Counts present', 'align' => 'center'],
                        ['key' => 'count', 'label' => 'Count', 'align' => 'center'],
                        ['key' => 'share', 'label' => 'Share', 'align' => 'center'],
                    ],
                    'rows' => $remarkRows,
                ] : null,
                $sectionRows !== [] ? [
                    'title' => 'By section (class attendance)',
                    'columns' => [
                        ['key' => 'section', 'label' => 'Section'],
                        ['key' => 'records', 'label' => 'Records', 'align' => 'center'],
                        ['key' => 'present', 'label' => 'Present', 'align' => 'center'],
                        ['key' => 'rate', 'label' => 'Rate', 'align' => 'center'],
                    ],
                    'rows' => $sectionRows,
                ] : null,
            ]),
            totalRows: count($dailyRows),
        );
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return list<array<string, mixed>>
     */
    protected function dailyBreakdownRows(Carbon $from, Carbon $to, array $filters): array
    {
        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $date = $cursor->toDateString();
            $gate = $this->gateQuery($cursor->copy()->startOfDay(), $cursor->copy()->endOfDay(), $filters)->get();
            $class = $this->classLogQuery($cursor->copy()->startOfDay(), $cursor->copy()->endOfDay(), $filters)->with('remark')->get();
            $classPresent = $class->filter(fn ($log) => $log->remark?->counts_as_present)->count();
            $classTotal = $class->count();

            $rows[] = [
                'date' => $cursor->format('M j, Y (D)'),
                'gate_records' => $gate->count(),
                'present' => $gate->filter(fn ($record) => $record->status === AttendanceStatus::Present)->count(),
                'late' => $gate->filter(fn ($record) => $record->status === AttendanceStatus::Late)->count(),
                'class_records' => $classTotal,
                'class_present_rate' => $classTotal > 0 ? round(($classPresent / $classTotal) * 100, 1).'%' : '—',
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    protected function dailyAttendance(Carbon $from, Carbon $to, string $periodLabel, array $filters): ReportPreview
    {
        $rows = $this->dailyBreakdownRows($from, $to, $filters);
        $gateTotal = collect($rows)->sum('gate_records');
        $classTotal = collect($rows)->sum('class_records');

        return new ReportPreview(
            title: 'Daily Attendance',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Days covered', 'value' => count($rows)],
                ['label' => 'Total gate records', 'value' => $gateTotal],
                ['label' => 'Total class records', 'value' => $classTotal],
            ],
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'gate_records', 'label' => 'Gate check-ins', 'align' => 'center'],
                ['key' => 'present', 'label' => 'Present', 'align' => 'center'],
                ['key' => 'late', 'label' => 'Late', 'align' => 'center'],
                ['key' => 'class_records', 'label' => 'Class logs', 'align' => 'center'],
                ['key' => 'class_present_rate', 'label' => 'Class present %', 'align' => 'center'],
            ],
            rows: $rows,
            totalRows: count($rows),
        );
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    protected function studentList(string $periodLabel, array $filters): ReportPreview
    {
        $students = $this->studentQuery($filters)
            ->where('status', StudentStatus::Active)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->get();

        $rows = $students->map(fn (Student $student) => [
            'student_number' => $student->student_number,
            'name' => $student->list_name,
            'grade' => $student->gradeLevel?->name ?? '—',
            'section' => $student->section?->name ?? '—',
            'gender' => $student->gender ? ucfirst($student->gender) : '—',
            'status' => $student->status?->label() ?? '—',
        ])->all();

        return new ReportPreview(
            title: 'Student List',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Active students', 'value' => count($rows)],
                ['label' => 'With section', 'value' => $students->whereNotNull('section_id')->count()],
                ['label' => 'Unassigned section', 'value' => $students->whereNull('section_id')->count()],
            ],
            columns: [
                ['key' => 'student_number', 'label' => 'Student No.'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'grade', 'label' => 'Grade'],
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'gender', 'label' => 'Sex', 'align' => 'center'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'center'],
            ],
            rows: $rows,
            totalRows: count($rows),
        );
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    protected function lateArrivals(Carbon $from, Carbon $to, string $periodLabel, array $filters): ReportPreview
    {
        $records = $this->gateQuery($from, $to, $filters)
            ->where('status', AttendanceStatus::Late->value)
            ->with(['student.gradeLevel', 'student.section'])
            ->orderByDesc('date')
            ->orderBy('time_in')
            ->get();

        $rows = $records->map(fn (AttendanceRecord $record) => [
            'date' => $record->date->format('M j, Y'),
            'student_number' => $record->student?->student_number ?? '—',
            'name' => $record->student?->list_name ?? '—',
            'grade' => $record->student?->gradeLevel?->name ?? '—',
            'section' => $record->student?->section?->name ?? '—',
            'time_in' => $record->time_in ? substr((string) $record->time_in, 0, 5) : '—',
            'remarks' => $record->remarks ?? '—',
        ])->all();

        return new ReportPreview(
            title: 'Late Arrivals',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Late records', 'value' => count($rows)],
                ['label' => 'Unique students', 'value' => $records->pluck('student_id')->unique()->count()],
            ],
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'student_number', 'label' => 'Student No.'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'grade', 'label' => 'Grade'],
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'time_in', 'label' => 'Time in', 'align' => 'center'],
                ['key' => 'remarks', 'label' => 'Remarks'],
            ],
            rows: $rows,
            totalRows: count($rows),
        );
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     */
    protected function absentStudents(Carbon $from, Carbon $to, string $periodLabel, array $filters): ReportPreview
    {
        $gateAbsents = $this->gateQuery($from, $to, $filters)
            ->where('status', AttendanceStatus::Absent->value)
            ->with(['student.gradeLevel', 'student.section'])
            ->get();

        $classAbsents = $this->classLogQuery($from, $to, $filters)
            ->whereHas('remark', fn (Builder $q) => $q->where('counts_as_present', false))
            ->with(['student.gradeLevel', 'student.section', 'remark', 'classSchedule.subject'])
            ->get();

        $rows = collect();

        foreach ($gateAbsents as $record) {
            $rows->push([
                'date' => $record->date->format('M j, Y'),
                'source' => 'Gate',
                'student_number' => $record->student?->student_number ?? '—',
                'name' => $record->student?->list_name ?? '—',
                'grade' => $record->student?->gradeLevel?->name ?? '—',
                'section' => $record->student?->section?->name ?? '—',
                'detail' => AttendanceStatus::Absent->label(),
                'remarks' => $record->remarks ?? '—',
            ]);
        }

        foreach ($classAbsents as $log) {
            $rows->push([
                'date' => $log->date->format('M j, Y'),
                'source' => 'Class',
                'student_number' => $log->student?->student_number ?? '—',
                'name' => $log->student?->list_name ?? '—',
                'grade' => $log->student?->gradeLevel?->name ?? '—',
                'section' => $log->section?->name ?? $log->student?->section?->name ?? '—',
                'detail' => ($log->classSchedule?->subject?->name ?? 'Class').': '.($log->remark?->label ?? '—'),
                'remarks' => $log->remarks ?? '—',
            ]);
        }

        $sorted = $rows->sortByDesc('date')->values()->all();

        return new ReportPreview(
            title: 'Absent Students',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Total absent entries', 'value' => count($sorted)],
                ['label' => 'Gate absences', 'value' => $gateAbsents->count()],
                ['label' => 'Class absences', 'value' => $classAbsents->count()],
                ['label' => 'Unique students', 'value' => $rows->pluck('student_number')->unique()->count()],
            ],
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'source', 'label' => 'Source', 'align' => 'center'],
                ['key' => 'student_number', 'label' => 'Student No.'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'grade', 'label' => 'Grade'],
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'detail', 'label' => 'Detail'],
                ['key' => 'remarks', 'label' => 'Remarks'],
            ],
            rows: $sorted,
            totalRows: count($sorted),
        );
    }

    protected function visitorLog(Carbon $from, Carbon $to, string $periodLabel): ReportPreview
    {
        $visitors = Visitor::query()
            ->whereBetween('time_in', [$from, $to])
            ->orderByDesc('time_in')
            ->get();

        $rows = $visitors->map(fn (Visitor $visitor) => [
            'date' => $visitor->time_in?->format('M j, Y') ?? '—',
            'name' => $visitor->name,
            'purpose' => $visitor->purpose ?? '—',
            'time_in' => $visitor->time_in?->format('h:i A') ?? '—',
            'time_out' => $visitor->time_out?->format('h:i A') ?? '—',
            'contact_person' => $visitor->contact_person ?? '—',
            'id_number' => $visitor->id_number ?? '—',
        ])->all();

        return new ReportPreview(
            title: 'Visitor Log',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Visitors', 'value' => count($rows)],
                ['label' => 'Still inside', 'value' => $visitors->whereNull('time_out')->count()],
            ],
            columns: [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'purpose', 'label' => 'Purpose'],
                ['key' => 'time_in', 'label' => 'Time in', 'align' => 'center'],
                ['key' => 'time_out', 'label' => 'Time out', 'align' => 'center'],
                ['key' => 'contact_person', 'label' => 'Contact'],
                ['key' => 'id_number', 'label' => 'ID No.'],
            ],
            rows: $rows,
            totalRows: count($rows),
        );
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return Builder<AttendanceRecord>
     */
    protected function gateQuery(Carbon $from, Carbon $to, array $filters): Builder
    {
        return AttendanceRecord::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->whereHas(
                'student',
                fn (Builder $student) => $student->where('section_id', $section),
            ))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->whereHas(
                'student',
                fn (Builder $student) => $student->where('grade_level_id', $grade),
            ))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'student.gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ));
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return Builder<AttendancePeriodLog>
     */
    protected function classLogQuery(Carbon $from, Carbon $to, array $filters): Builder
    {
        return AttendancePeriodLog::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->whereHas(
                'section',
                fn (Builder $section) => $section->where('grade_level_id', $grade),
            ))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'section.gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ));
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return Builder<Student>
     */
    protected function studentQuery(array $filters): Builder
    {
        return Student::query()
            ->with(['gradeLevel', 'section'])
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->where('grade_level_id', $grade))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ));
    }

    public function schoolName(): string
    {
        return $this->settings->get('school_name', config('app.name'), 'general') ?: config('app.name');
    }
}
