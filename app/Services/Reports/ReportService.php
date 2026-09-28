<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportPreview;
use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Visitor;
use App\Services\Settings\SettingsService;
use App\Services\Students\StudentListService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
            'research_attendance' => 'Research Attendance Report',
            'student_list' => 'Student List',
            'enrollment' => 'Enrollment Report',
            'late_arrivals' => 'Late Arrivals',
            'absent_students' => 'Absent Students',
            'visitor_log' => 'Visitor Log',
        ];
    }

    /**
     * Adopted attendance interpretation used by the research report.
     * 77.29% falls in 75.00–79.99 and is classified as Low.
     *
     * @return array{rating: string, interpretation: string, color: string}
     */
    public function interpretAttendanceRate(float $rate): array
    {
        return match (true) {
            $rate >= 90 => ['rating' => 'Outstanding', 'interpretation' => 'Very High', 'color' => '#059669'],
            $rate >= 85 => ['rating' => 'Very Satisfactory', 'interpretation' => 'High', 'color' => '#10b981'],
            $rate >= 80 => ['rating' => 'Satisfactory', 'interpretation' => 'Average', 'color' => '#d97706'],
            $rate >= 75 => ['rating' => 'Fairly Satisfactory', 'interpretation' => 'Low', 'color' => '#ea580c'],
            default => ['rating' => 'Did Not Meet Expectations', 'interpretation' => 'Very Low', 'color' => '#dc2626'],
        };
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @param  array{study_context?: ?string}  $options
     */
    public function preview(string $type, string $dateFrom, string $dateTo, array $filters = [], array $options = []): ReportPreview
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();
        $periodLabel = $from->format('M j, Y').' – '.$to->format('M j, Y');

        return match ($type) {
            'attendance_summary' => $this->attendanceSummary($from, $to, $periodLabel, $filters),
            'daily_attendance' => $this->dailyAttendance($from, $to, $periodLabel, $filters),
            'research_attendance' => $this->researchAttendance($from, $to, $periodLabel, $filters, $options),
            'student_list' => $this->studentList('Active students · '.now()->format('M j, Y'), $filters),
            'enrollment' => $this->enrollmentReport($filters),
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
        $students = StudentListService::orderByGenderThenName(
            $this->studentQuery($filters)
                ->where('status', StudentStatus::Active)
        )->get();

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
    protected function enrollmentReport(array $filters): ReportPreview
    {
        $yearId = $this->currentAcademicYearId();
        $academicYear = $yearId ? AcademicYear::query()->find($yearId) : null;
        $periodLabel = $academicYear
            ? 'School Year '.$academicYear->name.' · enrolled students'
            : 'Enrolled students';

        $enrollments = StudentEnrollment::query()
            ->where('status', EnrollmentStatus::Enrolled)
            ->when($yearId, fn (Builder $q) => $q->where('academic_year_id', $yearId))
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->where('grade_level_id', $grade))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ))
            ->with(['gradeLevel.department', 'section', 'student'])
            ->get();

        $countGender = fn (Collection $group, string $key): int => $group
            ->filter(fn (StudentEnrollment $e) => StudentListService::genderGroupKey($e->student?->gender) === $key)
            ->count();

        $gradeRows = $enrollments
            ->groupBy('grade_level_id')
            ->map(function (Collection $group) use ($countGender) {
                $gradeLevel = $group->first()?->gradeLevel;

                return [
                    'grade' => $gradeLevel?->name ?? 'Unassigned',
                    'department' => $gradeLevel?->department?->name ?? '—',
                    'male' => $countGender($group, 'male'),
                    'female' => $countGender($group, 'female'),
                    'total' => $group->count(),
                    '_sort' => $gradeLevel?->sort_order ?? 999,
                ];
            })
            ->sortBy('_sort')
            ->values();

        $rows = $gradeRows->map(fn (array $row) => Arr::except($row, ['_sort']))->all();

        $sectionRows = $enrollments
            ->groupBy('section_id')
            ->map(function (Collection $group) use ($countGender) {
                $section = $group->first()?->section;
                $gradeLevel = $group->first()?->gradeLevel;

                return [
                    'grade' => $gradeLevel?->name ?? '—',
                    'section' => $section?->name ?? 'Unassigned',
                    'male' => $countGender($group, 'male'),
                    'female' => $countGender($group, 'female'),
                    'total' => $group->count(),
                    '_grade_sort' => $gradeLevel?->sort_order ?? 999,
                    '_section' => $section?->name ?? '',
                ];
            })
            ->sortBy(['_grade_sort', '_section'])
            ->values();

        $sectionRowsOut = $sectionRows->map(fn (array $row) => Arr::except($row, ['_grade_sort', '_section']))->all();

        return new ReportPreview(
            title: 'Enrollment Report',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Enrolled students', 'value' => $enrollments->count()],
                ['label' => 'Year levels', 'value' => $enrollments->pluck('grade_level_id')->filter()->unique()->count()],
                ['label' => 'Sections', 'value' => $enrollments->pluck('section_id')->filter()->unique()->count()],
                ['label' => 'Male', 'value' => $countGender($enrollments, 'male')],
                ['label' => 'Female', 'value' => $countGender($enrollments, 'female')],
                ['label' => 'Unassigned section', 'value' => $enrollments->whereNull('section_id')->count()],
            ],
            columns: [
                ['key' => 'grade', 'label' => 'Year Level'],
                ['key' => 'department', 'label' => 'Department'],
                ['key' => 'male', 'label' => 'Male', 'align' => 'center'],
                ['key' => 'female', 'label' => 'Female', 'align' => 'center'],
                ['key' => 'total', 'label' => 'Enrolled', 'align' => 'center'],
            ],
            rows: $rows,
            tables: array_filter([
                $sectionRowsOut !== [] ? [
                    'title' => 'Enrolled per section',
                    'columns' => [
                        ['key' => 'grade', 'label' => 'Year Level'],
                        ['key' => 'section', 'label' => 'Section'],
                        ['key' => 'male', 'label' => 'Male', 'align' => 'center'],
                        ['key' => 'female', 'label' => 'Female', 'align' => 'center'],
                        ['key' => 'total', 'label' => 'Enrolled', 'align' => 'center'],
                    ],
                    'rows' => $sectionRowsOut,
                ] : null,
            ]),
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
     * Narrative research report: identified learners, attendance rate,
     * adopted interpretation, grade distribution, and bar-chart series.
     *
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @param  array{study_context?: ?string}  $options
     */
    protected function researchAttendance(Carbon $from, Carbon $to, string $periodLabel, array $filters, array $options): ReportPreview
    {
        $classLogs = $this->classLogQuery($from, $to, $filters)
            ->with(['remark', 'student.gradeLevel', 'student.section', 'section.gradeLevel'])
            ->get();

        $usesClassLogs = $classLogs->isNotEmpty();

        $records = $usesClassLogs
            ? $classLogs
            : $this->gateQuery($from, $to, $filters)->with(['student.gradeLevel', 'student.section'])->get();

        $records = $records->filter(fn ($record) => $record->student_id && $record->student)->values();

        $sourceNote = $usesClassLogs
            ? 'Rates use class attendance. A record counts as attended when its remark is set to count as present (Present and Late, by default).'
            : 'Rates use gate check-ins. Present and Late count as attended.';

        $learnerRows = $records
            ->groupBy('student_id')
            ->map(function (Collection $logs) use ($usesClassLogs) {
                $record = $logs->first();
                $student = $record->student;
                $gradeLevel = $student->gradeLevel;
                if ($gradeLevel === null && $record instanceof AttendancePeriodLog) {
                    $gradeLevel = $record->section?->gradeLevel;
                }
                $present = $usesClassLogs
                    ? $logs->filter(fn ($log) => (bool) $log->remark?->counts_as_present)->count()
                    : $logs->filter(fn ($record) => in_array($record->status, [AttendanceStatus::Present, AttendanceStatus::Late], true))->count();
                $total = $logs->count();
                $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;
                $band = $this->interpretAttendanceRate($rate);

                return [
                    'student_number' => $student->student_number,
                    'name' => $student->list_name,
                    'grade' => $gradeLevel?->name ?? 'Unassigned',
                    'section' => $student->section?->name ?? '—',
                    'present' => $present,
                    'records' => $total,
                    'rate' => number_format($rate, 2).'%',
                    'interpretation' => $band['interpretation'],
                    '_rate' => $rate,
                    '_sort' => $gradeLevel?->sort_order ?? 999,
                    '_grade_id' => $gradeLevel?->id ?? 0,
                ];
            })
            ->sortBy(['_sort', 'name'])
            ->values();

        $presentTotal = (int) $learnerRows->sum('present');
        $recordTotal = (int) $learnerRows->sum('records');
        $learnerCount = $learnerRows->count();
        $overallRate = $recordTotal > 0 ? round(($presentTotal / $recordTotal) * 100, 2) : 0.0;
        $overallBand = $this->interpretAttendanceRate($overallRate);

        $gradeRows = $learnerRows
            ->groupBy('_grade_id')
            ->map(function (Collection $group) {
                $present = (int) $group->sum('present');
                $total = (int) $group->sum('records');
                $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;
                $band = $this->interpretAttendanceRate($rate);

                return [
                    'grade' => $group->first()['grade'],
                    'learners' => $group->count(),
                    'present' => $present,
                    'records' => $total,
                    'rate' => number_format($rate, 2).'%',
                    'interpretation' => $band['interpretation'],
                    '_rate' => $rate,
                    '_sort' => $group->first()['_sort'],
                    '_color' => $band['color'],
                ];
            })
            ->sortBy('_sort')
            ->values();

        $scaleRows = [
            ['range' => '90.00% – 100%', 'rating' => 'Outstanding', 'interpretation' => 'Very High'],
            ['range' => '85.00% – 89.99%', 'rating' => 'Very Satisfactory', 'interpretation' => 'High'],
            ['range' => '80.00% – 84.99%', 'rating' => 'Satisfactory', 'interpretation' => 'Average'],
            ['range' => '75.00% – 79.99%', 'rating' => 'Fairly Satisfactory', 'interpretation' => 'Low'],
            ['range' => 'Below 75.00%', 'rating' => 'Did Not Meet Expectations', 'interpretation' => 'Very Low'],
        ];

        $narrative = $learnerCount === 0
            ? 'No attendance records were found for '.$periodLabel.'. No learners could be identified in the selected scope.'
            : $this->researchNarrative($learnerCount, $overallRate, $overallBand['interpretation'], $gradeRows->all(), $periodLabel, $options['study_context'] ?? null);

        $charts = $learnerCount === 0 ? [] : $this->researchCharts($from, $to, $records, $usesClassLogs, $gradeRows);

        $detailRows = $learnerRows->map(fn (array $row) => Arr::except($row, ['_rate', '_sort', '_grade_id']))->all();
        $gradeTableRows = $gradeRows->map(fn (array $row) => Arr::except($row, ['_rate', '_sort', '_color']))->all();

        return new ReportPreview(
            title: 'Research Attendance Report',
            periodLabel: $periodLabel,
            summaryStats: [
                ['label' => 'Identified learners', 'value' => $learnerCount],
                ['label' => 'Attendance rate', 'value' => $recordTotal > 0 ? number_format($overallRate, 2).'%' : '—', 'hint' => $recordTotal > 0 ? $overallBand['rating'] : null],
                ['label' => 'Interpretation', 'value' => $recordTotal > 0 ? $overallBand['interpretation'] : '—'],
                ['label' => 'Attended records', 'value' => $presentTotal],
                ['label' => 'Total records', 'value' => $recordTotal],
                ['label' => 'Grade levels', 'value' => $gradeRows->count()],
            ],
            columns: [
                ['key' => 'student_number', 'label' => 'Student No.'],
                ['key' => 'name', 'label' => 'Learner'],
                ['key' => 'grade', 'label' => 'Grade'],
                ['key' => 'section', 'label' => 'Section'],
                ['key' => 'present', 'label' => 'Attended', 'align' => 'center'],
                ['key' => 'records', 'label' => 'Records', 'align' => 'center'],
                ['key' => 'rate', 'label' => 'Rate', 'align' => 'center'],
                ['key' => 'interpretation', 'label' => 'Interpretation', 'align' => 'center'],
            ],
            rows: $detailRows,
            tables: array_values(array_filter([
                $gradeTableRows !== [] ? [
                    'title' => 'Table 1. Attendance rate by grade level',
                    'columns' => [
                        ['key' => 'grade', 'label' => 'Grade'],
                        ['key' => 'learners', 'label' => 'Learners', 'align' => 'center'],
                        ['key' => 'present', 'label' => 'Attended', 'align' => 'center'],
                        ['key' => 'records', 'label' => 'Records', 'align' => 'center'],
                        ['key' => 'rate', 'label' => 'Attendance rate', 'align' => 'center'],
                        ['key' => 'interpretation', 'label' => 'Interpretation', 'align' => 'center'],
                    ],
                    'rows' => $gradeTableRows,
                ] : null,
                [
                    'title' => 'Table 2. Adopted attendance interpretation',
                    'columns' => [
                        ['key' => 'range', 'label' => 'Attendance rate'],
                        ['key' => 'rating', 'label' => 'Descriptive rating'],
                        ['key' => 'interpretation', 'label' => 'Interpretation'],
                    ],
                    'rows' => $scaleRows,
                ],
            ])),
            totalRows: count($detailRows),
            charts: $charts,
            narrative: $narrative,
            narrativeNote: $learnerCount === 0 ? null : $sourceNote,
            rowsTitle: $detailRows !== [] ? 'Table 3. Attendance of identified learners' : null,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $gradeRows
     */
    protected function researchNarrative(int $learnerCount, float $rate, string $interpretation, array $gradeRows, string $periodLabel, ?string $studyContext): string
    {
        $context = trim(strip_tags((string) $studyContext));
        $context = rtrim($context, " \t\n\r\0\x0B.");
        if (mb_strlen($context) > 180) {
            $context = rtrim(mb_substr($context, 0, 180));
        }

        $examined = $context !== '' ? $context : 'during '.$periodLabel;
        $learnerLabel = $learnerCount.' identified '.str('learner')->plural($learnerCount);

        return 'The attendance records of the '.$learnerLabel.' were examined '.$examined
            .'. As shown in Table 1, the overall attendance rate was '.number_format($rate, 2)
            .'%, classified as '.$interpretation.' based on the adopted attendance interpretation. '
            .$this->describeGradeDistribution($gradeRows);
    }

    /**
     * @param  list<array<string, mixed>>  $gradeRows
     */
    protected function describeGradeDistribution(array $gradeRows): string
    {
        $parts = [];

        foreach (array_values($gradeRows) as $index => $grade) {
            $count = (int) $grade['learners'];
            $countLabel = $index === 0
                ? $count.' '.str('learner')->plural($count)
                : (string) $count;
            $parts[] = $grade['grade'].' ('.$countLabel.')';
        }

        $list = match (count($parts)) {
            0 => 'no grade level',
            1 => $parts[0],
            2 => $parts[0].' and '.$parts[1],
            default => implode(', ', array_slice($parts, 0, -1)).', and '.$parts[array_key_last($parts)],
        };

        if (count($parts) <= 1) {
            return 'The learners were distributed in '.$list.'.';
        }

        return 'The learners were distributed across '.$list.'.';
    }

    /**
     * @param  Collection<int, mixed>  $records
     * @param  Collection<int, array<string, mixed>>  $gradeRows
     * @return list<array<string, mixed>>
     */
    protected function researchCharts(Carbon $from, Carbon $to, Collection $records, bool $usesClassLogs, Collection $gradeRows): array
    {
        $gradeLabels = $gradeRows->pluck('grade')->all();
        $gradeRates = $gradeRows->pluck('_rate')->map(fn ($rate) => (float) $rate)->all();
        $gradeColors = $gradeRows->pluck('_color')->all();
        $learnerCounts = $gradeRows->pluck('learners')->map(fn ($count) => (int) $count)->all();

        $daySpan = (int) abs($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) + 1;
        $byPeriod = $daySpan <= 45;

        $trendGroups = $records->groupBy(function ($record) use ($byPeriod) {
            $date = $record->date instanceof Carbon ? $record->date : Carbon::parse($record->date);

            return $byPeriod ? $date->toDateString() : $date->format('Y-m');
        })->sortKeys();

        $trendLabels = [];
        $trendRates = [];
        $trendColors = [];

        foreach ($trendGroups as $key => $group) {
            $present = $usesClassLogs
                ? $group->filter(fn ($log) => (bool) $log->remark?->counts_as_present)->count()
                : $group->filter(fn ($record) => in_array($record->status, [AttendanceStatus::Present, AttendanceStatus::Late], true))->count();
            $total = $group->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;
            $date = $byPeriod ? Carbon::parse($key) : Carbon::parse($key.'-01');

            $trendLabels[] = $byPeriod ? $date->format('M j') : $date->format('M Y');
            $trendRates[] = $rate;
            $trendColors[] = $this->interpretAttendanceRate($rate)['color'];
        }

        return [
            [
                'key' => 'grade_rate',
                'title' => 'Attendance rate by grade',
                'subtitle' => 'Bar height is the attendance rate of identified learners in each grade',
                'labels' => $gradeLabels,
                'datasets' => [[
                    'label' => 'Attendance rate',
                    'data' => $gradeRates,
                    'colors' => $gradeColors,
                ]],
                'yMax' => 100,
                'suffix' => '%',
            ],
            [
                'key' => 'grade_learners',
                'title' => 'Identified learners by grade',
                'subtitle' => 'How the examined learners are distributed across grade levels',
                'labels' => $gradeLabels,
                'datasets' => [[
                    'label' => 'Learners',
                    'data' => $learnerCounts,
                    'colors' => '#7c3aed',
                ]],
                'yMax' => null,
                'suffix' => '',
            ],
            [
                'key' => 'trend',
                'title' => $byPeriod ? 'Daily attendance rate' : 'Monthly attendance rate',
                'subtitle' => $byPeriod
                    ? 'Each bar is a day that has attendance records'
                    : 'Each bar is a month that has attendance records',
                'labels' => $trendLabels,
                'datasets' => [[
                    'label' => 'Attendance rate',
                    'data' => $trendRates,
                    'colors' => $trendColors,
                ]],
                'yMax' => 100,
                'suffix' => '%',
                'wide' => true,
            ],
        ];
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

    protected function currentAcademicYearId(): ?int
    {
        return AcademicYear::query()->where('is_current', true)->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id');
    }
}
