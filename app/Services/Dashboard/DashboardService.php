<?php

namespace App\Services\Dashboard;

use App\Enums\AttendanceStatus;
use App\Enums\AuditAction;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Visitor;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\ClassScheduleResolver;
use App\Services\Attendance\DailyAttendanceResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected ClassScheduleResolver $scheduleResolver,
        protected DailyAttendanceResolver $attendanceResolver,
    ) {}

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return array<string, mixed>
     */
    public function data(
        ?Carbon $referenceDate = null,
        string $period = 'day',
        array $filters = [],
    ): array {
        $referenceDate ??= Carbon::today();
        $period = in_array($period, ['day', 'week', 'month'], true) ? $period : 'day';
        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($referenceDate, $period);
        $focusDate = $rangeEnd->copy();
        $isSingleDay = $period === 'day';

        $activeStudents = $this->activeStudentQuery($filters)->pluck('id');
        $totalStudents = $activeStudents->count();

        $rangeStatuses = $this->attendanceResolver->resolveForDateRange(
            $rangeStart,
            $rangeEnd,
            $activeStudents,
        );

        $focusStatuses = $this->statusesForDate($rangeStatuses, $activeStudents, $focusDate);
        $comparisonDate = $isSingleDay
            ? $focusDate->copy()->subDay()
            : $rangeStart->copy()->subDay();
        $comparisonStatuses = $this->attendanceResolver->resolveForStudents($comparisonDate, $activeStudents);

        $periodTotals = $this->aggregateStatuses($rangeStatuses, $activeStudents, $rangeStart, $rangeEnd);
        $focusTotals = $this->countStatuses($focusStatuses, $totalStudents);

        $statsSource = $isSingleDay ? $focusTotals : $periodTotals;
        $comparisonPresent = $comparisonStatuses->filter(fn ($status) => $status === AttendanceStatus::Present)->count();
        $presentTrendBase = $isSingleDay ? $focusTotals['present'] : $periodTotals['present'];
        $presentTrend = $comparisonPresent > 0
            ? round((($presentTrendBase - $comparisonPresent) / $comparisonPresent) * 100, 1)
            : ($presentTrendBase > 0 ? 100 : 0);

        $pct = fn (int $value): float => $statsSource['denominator'] > 0
            ? round(($value / $statsSource['denominator']) * 100, 1)
            : 0;

        $attended = $statsSource['present'] + $statsSource['late'] + $statsSource['excused'];
        $attendancePercent = $statsSource['denominator'] > 0
            ? round(($attended / $statsSource['denominator']) * 100, 1)
            : 0;

        [$trendLabels, $trendData] = $this->trendSeries(
            $period,
            $referenceDate,
            $rangeStart,
            $rangeEnd,
            $activeStudents,
            $totalStudents,
            $rangeStatuses,
        );

        $statusBreakdown = [
            'Present' => $statsSource['present'],
            'Late' => $statsSource['late'],
            'Excused' => $statsSource['excused'],
            'Absent' => $statsSource['explicit_absent'] + $statsSource['half_day'],
            'Not recorded' => $statsSource['not_recorded'],
        ];

        $classAbsencesFocus = AttendancePeriodLog::query()
            ->whereDate('date', $focusDate->toDateString())
            ->whereHas('remark', fn ($q) => $q->where('counts_as_present', false))
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->whereHas(
                'section',
                fn (Builder $section) => $section->where('grade_level_id', $grade),
            ))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'section.gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ))
            ->count();

        $topAbsentees = $this->topAbsentees($rangeStart, $rangeEnd, $filters);
        $maxAbsences = $topAbsentees->max('absences_count') ?: 1;

        $detailedReport = $this->detailedAttendanceReport(
            $rangeStart,
            $rangeEnd,
            $activeStudents,
            $totalStudents,
            $rangeStatuses,
        );

        $sectionBreakdown = $this->sectionAttendanceBreakdown(
            $rangeStart,
            $rangeEnd,
            $filters,
            $rangeStatuses,
        );

        $departmentBreakdown = $this->departmentAttendanceBreakdown(
            $focusDate,
            $filters,
        );

        $focusRecords = AttendanceRecord::query()
            ->with(['student.gradeLevel', 'student.section'])
            ->whereDate('date', $focusDate->toDateString())
            ->when($activeStudents->isNotEmpty(), fn (Builder $q) => $q->whereIn('student_id', $activeStudents))
            ->when($activeStudents->isEmpty(), fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->get();

        $lateArrivals = $focusRecords
            ->filter(fn (AttendanceRecord $record) => $record->status === AttendanceStatus::Late)
            ->sortByDesc('time_in')
            ->take(8)
            ->values();

        $recentCheckIns = $focusRecords
            ->whereNotNull('time_in')
            ->sortByDesc(fn (AttendanceRecord $record) => $record->time_in)
            ->take(8)
            ->values();

        $visitorsInside = Visitor::query()
            ->whereDate('time_in', Carbon::today()->toDateString())
            ->whereNull('time_out')
            ->count();

        return [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period, $rangeStart, $rangeEnd),
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'focusDate' => $focusDate,
            'isSingleDay' => $isSingleDay,
            'stats' => [
                'present' => $statsSource['present'],
                'late' => $statsSource['late'],
                'absent' => $statsSource['absent'],
                'explicit_absent' => $statsSource['explicit_absent'],
                'half_day' => $statsSource['half_day'],
                'not_recorded' => $statsSource['not_recorded'],
                'excused' => $statsSource['excused'],
                'visitors' => Visitor::query()->whereDate('time_in', $focusDate->toDateString())->count(),
                'visitors_inside' => $visitorsInside,
                'checkouts' => $focusRecords->whereNotNull('time_out')->count(),
                'attendance_percent' => $attendancePercent,
                'total_students' => $totalStudents,
                'recorded_today' => $focusTotals['recorded'],
                'recorded' => $statsSource['recorded'],
                'denominator' => $statsSource['denominator'],
                'present_pct' => $pct($statsSource['present']),
                'late_pct' => $pct($statsSource['late']),
                'absent_pct' => $pct($statsSource['absent']),
                'excused_pct' => $pct($statsSource['excused']),
                'present_trend' => $presentTrend,
                'student_days' => $periodTotals['denominator'],
                'avg_daily_attendance' => $this->averageDailyAttendancePercent(
                    $rangeStart,
                    $rangeEnd,
                    $activeStudents,
                    $totalStudents,
                    $rangeStatuses,
                ),
            ],
            'liveOverview' => [
                'inside_campus' => $this->attendanceService->getStudentsInsideCampus(Carbon::today())->count(),
                'classes_in_session' => $this->classesInSessionCount(Carbon::today()),
                'active_alerts' => $focusTotals['explicit_absent'] + $classAbsencesFocus,
                'pending_approvals' => StudentEnrollment::query()
                    ->where('status', EnrollmentStatus::Pending)
                    ->count(),
                'visitors_inside' => $visitorsInside,
                'half_day' => $focusTotals['half_day'],
            ],
            'weeklyLabels' => $trendLabels,
            'weeklyData' => $trendData,
            'trendTitle' => match ($period) {
                'week' => 'Weekly Attendance Trend',
                'month' => 'Monthly Attendance Trend',
                default => 'Attendance Trend (7 days)',
            },
            'statusBreakdown' => $statusBreakdown,
            'statusBreakdownColors' => [
                'Present' => '#22c55e',
                'Late' => '#f59e0b',
                'Excused' => '#3b82f6',
                'Absent' => '#ef4444',
                'Not recorded' => '#94a3b8',
            ],
            'topAbsentees' => $topAbsentees,
            'maxAbsences' => $maxAbsences,
            'recentActivity' => $this->recentActivity(),
            'todaysSchedules' => $this->todaysSchedules($focusDate, $filters),
            'recentCheckIns' => $recentCheckIns,
            'lateArrivals' => $lateArrivals,
            'detailedReport' => $detailedReport,
            'sectionBreakdown' => $sectionBreakdown,
            'departmentBreakdown' => $departmentBreakdown,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriodRange(Carbon $referenceDate, string $period): array
    {
        $end = $referenceDate->copy()->startOfDay();

        return match ($period) {
            'week' => [$end->copy()->startOfWeek(), $end],
            'month' => [$end->copy()->startOfMonth(), $end],
            default => [$end->copy(), $end],
        };
    }

    public function periodLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'week' => $start->format('M j').' – '.$end->format('M j, Y'),
            'month' => $start->format('F Y').($start->isSameDay($end) ? '' : ' (through '.$end->format('M j').')'),
            default => $end->format('l, F j, Y'),
        };
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return array{
     *     category: string,
     *     title: string,
     *     subtitle: string,
     *     is_single_day: bool,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     groups: list<array{label: string, count: int}>
     * }
     */
    public function statusDetails(
        string $category,
        ?Carbon $referenceDate = null,
        string $period = 'day',
        array $filters = [],
    ): array {
        $referenceDate ??= Carbon::today();
        $period = in_array($period, ['day', 'week', 'month'], true) ? $period : 'day';
        $category = in_array($category, [
            'attended', 'present', 'late', 'excused', 'absent', 'half_day',
            'not_recorded', 'visitors', 'checkouts', 'recorded',
        ], true) ? $category : 'present';

        [$rangeStart, $rangeEnd] = $this->resolvePeriodRange($referenceDate, $period);
        $focusDate = $rangeEnd->copy();
        $isSingleDay = $period === 'day';
        $periodLabel = $this->periodLabel($period, $rangeStart, $rangeEnd);

        if ($category === 'visitors') {
            return $this->visitorDetails($focusDate, $periodLabel);
        }

        if ($category === 'checkouts') {
            return $this->checkoutDetails($focusDate, $periodLabel, $filters);
        }

        $students = $this->activeStudentQuery($filters)
            ->with(['gradeLevel', 'section'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->keyBy('id');

        $studentIds = $students->keys();
        $rangeStatuses = $this->attendanceResolver->resolveForDateRange($rangeStart, $rangeEnd, $studentIds);
        $focusRecords = AttendanceRecord::query()
            ->whereDate('date', $focusDate->toDateString())
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $title = match ($category) {
            'attended' => 'Attended students',
            'present' => 'Present students',
            'late' => 'Late students',
            'excused' => 'Excused students',
            'half_day' => 'Half-day students',
            'not_recorded' => 'Not recorded',
            'recorded' => 'Recorded students',
            default => 'Absent students',
        };

        $rows = [];
        $groupCounts = [];

        foreach ($students as $student) {
            if ($isSingleDay) {
                $status = $rangeStatuses->get($student->id.'|'.$focusDate->toDateString());
                if (! $this->studentMatchesCategory($status, $category)) {
                    continue;
                }

                $statusLabel = $this->detailStatusLabel($status, $category);
                $groupCounts[$statusLabel] = ($groupCounts[$statusLabel] ?? 0) + 1;
                $record = $focusRecords->get($student->id);

                $rows[] = [
                    'student_id' => $student->id,
                    'name' => $student->list_name,
                    'student_number' => $student->student_number,
                    'grade' => $student->gradeLevel?->name ?? '—',
                    'section' => $student->section?->name ?? '—',
                    'status' => $status?->value,
                    'status_label' => $statusLabel,
                    'time_in' => $record?->time_in ? substr((string) $record->time_in, 0, 5) : null,
                    'days' => null,
                ];

                continue;
            }

            $matchedDays = 0;
            $latestStatus = null;

            for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }

                $status = $rangeStatuses->get($student->id.'|'.$date->toDateString());

                if ($this->studentMatchesCategory($status, $category)) {
                    $matchedDays++;
                    $latestStatus = $status;
                }
            }

            if ($matchedDays === 0) {
                continue;
            }

            $statusLabel = $this->detailStatusLabel($latestStatus, $category);
            $groupCounts[$statusLabel] = ($groupCounts[$statusLabel] ?? 0) + 1;

            $rows[] = [
                'student_id' => $student->id,
                'name' => $student->list_name,
                'student_number' => $student->student_number,
                'grade' => $student->gradeLevel?->name ?? '—',
                'section' => $student->section?->name ?? '—',
                'status' => $latestStatus?->value,
                'status_label' => $statusLabel,
                'time_in' => null,
                'days' => $matchedDays,
            ];
        }

        usort($rows, function (array $a, array $b) use ($isSingleDay) {
            if (! $isSingleDay && ($b['days'] ?? 0) !== ($a['days'] ?? 0)) {
                return ($b['days'] ?? 0) <=> ($a['days'] ?? 0);
            }

            return strcmp($a['name'], $b['name']);
        });

        return [
            'category' => $category,
            'title' => $title,
            'subtitle' => $periodLabel,
            'is_single_day' => $isSingleDay,
            'columns' => $isSingleDay
                ? ['Student', 'Grade / Section', 'Status', 'Time in']
                : ['Student', 'Grade / Section', 'Status', 'Days'],
            'rows' => $rows,
            'groups' => collect($groupCounts)
                ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                ->values()
                ->all(),
        ];
    }

    protected function studentMatchesCategory(?AttendanceStatus $status, string $category): bool
    {
        return match ($category) {
            'attended' => $this->attendanceResolver->isAttended($status),
            'present' => $status === AttendanceStatus::Present,
            'late' => $status === AttendanceStatus::Late,
            'excused' => $status === AttendanceStatus::Excused,
            'half_day' => $status === AttendanceStatus::HalfDay,
            'not_recorded' => $status === null,
            'recorded' => $status !== null,
            'absent' => $this->attendanceResolver->isAbsentDay($status),
            default => false,
        };
    }

    protected function detailStatusLabel(?AttendanceStatus $status, string $category): string
    {
        if ($status === null) {
            return 'Not recorded';
        }

        return $status->label();
    }

    /**
     * @return array{
     *     category: string,
     *     title: string,
     *     subtitle: string,
     *     is_single_day: bool,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     groups: list<array{label: string, count: int}>
     * }
     */
    protected function visitorDetails(Carbon $focusDate, string $periodLabel): array
    {
        $rows = Visitor::query()
            ->whereDate('time_in', $focusDate->toDateString())
            ->orderByDesc('time_in')
            ->get()
            ->map(fn (Visitor $visitor) => [
                'student_id' => null,
                'name' => $visitor->name,
                'student_number' => $visitor->id_number ?: '—',
                'grade' => $visitor->purpose ?: 'Visitor',
                'section' => $visitor->contact_person ? 'Host: '.$visitor->contact_person : '—',
                'status' => $visitor->time_out ? 'checked_out' : 'inside',
                'status_label' => $visitor->time_out ? 'Checked out' : 'Still inside',
                'time_in' => $visitor->time_in?->format('H:i'),
                'days' => null,
            ])
            ->all();

        return [
            'category' => 'visitors',
            'title' => 'Visitors',
            'subtitle' => $periodLabel,
            'is_single_day' => true,
            'columns' => ['Visitor', 'Purpose / Host', 'Status', 'Time in'],
            'rows' => $rows,
            'groups' => [
                ['label' => 'Total', 'count' => count($rows)],
            ],
        ];
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return array{
     *     category: string,
     *     title: string,
     *     subtitle: string,
     *     is_single_day: bool,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     groups: list<array{label: string, count: int}>
     * }
     */
    protected function checkoutDetails(Carbon $focusDate, string $periodLabel, array $filters): array
    {
        $studentIds = $this->activeStudentQuery($filters)->pluck('id');

        $rows = AttendanceRecord::query()
            ->with(['student.gradeLevel', 'student.section'])
            ->whereDate('date', $focusDate->toDateString())
            ->whereNotNull('time_out')
            ->when($studentIds->isNotEmpty(), fn (Builder $q) => $q->whereIn('student_id', $studentIds))
            ->when($studentIds->isEmpty(), fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('time_out')
            ->get()
            ->map(fn (AttendanceRecord $record) => [
                'student_id' => $record->student_id,
                'name' => $record->student?->list_name ?? 'Student',
                'student_number' => $record->student?->student_number ?? '—',
                'grade' => $record->student?->gradeLevel?->name ?? '—',
                'section' => $record->student?->section?->name ?? '—',
                'status' => $record->status?->value,
                'status_label' => $record->status?->label() ?? 'Checked out',
                'time_in' => $record->time_out ? substr((string) $record->time_out, 0, 5) : null,
                'days' => null,
            ])
            ->all();

        return [
            'category' => 'checkouts',
            'title' => 'Checkouts',
            'subtitle' => $periodLabel,
            'is_single_day' => true,
            'columns' => ['Student', 'Grade / Section', 'Status', 'Time out'],
            'rows' => $rows,
            'groups' => [
                ['label' => 'Total', 'count' => count($rows)],
            ],
        ];
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return Builder<Student>
     */
    protected function activeStudentQuery(array $filters): Builder
    {
        return Student::query()
            ->where('status', StudentStatus::Active)
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->where('grade_level_id', $grade))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ));
    }

    /**
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     * @param  Collection<int, int>  $studentIds
     * @return Collection<int, AttendanceStatus|null>
     */
    protected function statusesForDate(Collection $rangeStatuses, Collection $studentIds, Carbon $date): Collection
    {
        $dateString = $date->toDateString();

        return $studentIds->mapWithKeys(
            fn (int $studentId) => [$studentId => $rangeStatuses->get($studentId.'|'.$dateString)],
        );
    }

    /**
     * @param  Collection<int, AttendanceStatus|null>  $statuses
     * @return array{
     *     present: int,
     *     late: int,
     *     excused: int,
     *     explicit_absent: int,
     *     half_day: int,
     *     recorded: int,
     *     not_recorded: int,
     *     absent: int,
     *     denominator: int
     * }
     */
    protected function countStatuses(Collection $statuses, int $totalStudents): array
    {
        $present = $statuses->filter(fn ($status) => $status === AttendanceStatus::Present)->count();
        $late = $statuses->filter(fn ($status) => $status === AttendanceStatus::Late)->count();
        $excused = $statuses->filter(fn ($status) => $status === AttendanceStatus::Excused)->count();
        $explicitAbsent = $statuses->filter(fn ($status) => $status === AttendanceStatus::Absent)->count();
        $halfDay = $statuses->filter(fn ($status) => $status === AttendanceStatus::HalfDay)->count();
        $recorded = $statuses->filter(fn ($status) => $status !== null)->count();
        $notRecorded = max(0, $totalStudents - $recorded);

        return [
            'present' => $present,
            'late' => $late,
            'excused' => $excused,
            'explicit_absent' => $explicitAbsent,
            'half_day' => $halfDay,
            'recorded' => $recorded,
            'not_recorded' => $notRecorded,
            'absent' => $explicitAbsent + $halfDay + $notRecorded,
            'denominator' => $totalStudents,
        ];
    }

    /**
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     * @param  Collection<int, int>  $studentIds
     * @return array{
     *     present: int,
     *     late: int,
     *     excused: int,
     *     explicit_absent: int,
     *     half_day: int,
     *     recorded: int,
     *     not_recorded: int,
     *     absent: int,
     *     denominator: int
     * }
     */
    protected function aggregateStatuses(
        Collection $rangeStatuses,
        Collection $studentIds,
        Carbon $from,
        Carbon $to,
    ): array {
        $totals = [
            'present' => 0,
            'late' => 0,
            'excused' => 0,
            'explicit_absent' => 0,
            'half_day' => 0,
            'recorded' => 0,
            'not_recorded' => 0,
            'absent' => 0,
            'denominator' => 0,
        ];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $dayStatuses = $this->statusesForDate($rangeStatuses, $studentIds, $date);
            $dayTotals = $this->countStatuses($dayStatuses, $studentIds->count());

            foreach (['present', 'late', 'excused', 'explicit_absent', 'half_day', 'recorded', 'not_recorded', 'absent', 'denominator'] as $key) {
                $totals[$key] += $dayTotals[$key];
            }
        }

        return $totals;
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     * @return array{0: list<string>, 1: list<int>}
     */
    protected function trendSeries(
        string $period,
        Carbon $referenceDate,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        Collection $studentIds,
        int $totalStudents,
        Collection $rangeStatuses,
    ): array {
        if ($period === 'day') {
            $labels = [];
            $data = [];
            $trendStart = $referenceDate->copy()->subDays(6);
            $trendStatuses = $this->attendanceResolver->resolveForDateRange(
                $trendStart,
                $referenceDate,
                $studentIds,
            );

            for ($d = 6; $d >= 0; $d--) {
                $date = $referenceDate->copy()->subDays($d);
                $labels[] = $date->format('D');
                $data[] = $this->attendancePercentForDate($date, $studentIds, $totalStudents, $trendStatuses);
            }

            return [$labels, $data];
        }

        $labels = [];
        $data = [];

        for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
            $labels[] = $date->format($period === 'month' ? 'j' : 'D');
            $data[] = $this->attendancePercentForDate($date, $studentIds, $totalStudents, $rangeStatuses);
        }

        return [$labels, $data];
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     */
    protected function attendancePercentForDate(
        Carbon $date,
        Collection $studentIds,
        int $totalStudents,
        Collection $rangeStatuses,
    ): int {
        if ($totalStudents === 0) {
            return 0;
        }

        $dateString = $date->toDateString();
        $attendedCount = $studentIds->filter(function (int $studentId) use ($dateString, $rangeStatuses) {
            return $this->attendanceResolver->isAttended(
                $rangeStatuses->get($studentId.'|'.$dateString),
            );
        })->count();

        return (int) round(($attendedCount / $totalStudents) * 100);
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     */
    protected function averageDailyAttendancePercent(
        Carbon $from,
        Carbon $to,
        Collection $studentIds,
        int $totalStudents,
        Collection $rangeStatuses,
    ): float {
        if ($totalStudents === 0) {
            return 0;
        }

        $percents = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $percents[] = $this->attendancePercentForDate($date, $studentIds, $totalStudents, $rangeStatuses);
        }

        if ($percents === []) {
            return 0;
        }

        return round(array_sum($percents) / count($percents), 1);
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     * @return list<array<string, mixed>>
     */
    protected function detailedAttendanceReport(
        Carbon $from,
        Carbon $to,
        Collection $studentIds,
        int $totalStudents,
        Collection $rangeStatuses,
    ): array {
        $rows = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $dayStatuses = $this->statusesForDate($rangeStatuses, $studentIds, $date);
            $counts = $this->countStatuses($dayStatuses, $totalStudents);
            $attended = $counts['present'] + $counts['late'] + $counts['excused'];
            $rate = $totalStudents > 0 ? round(($attended / $totalStudents) * 100, 1) : 0;

            $rows[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('M j, Y (D)'),
                'is_weekend' => $date->isWeekend(),
                'present' => $counts['present'],
                'late' => $counts['late'],
                'excused' => $counts['excused'],
                'absent' => $counts['explicit_absent'] + $counts['half_day'],
                'not_recorded' => $counts['not_recorded'],
                'recorded' => $counts['recorded'],
                'attendance_percent' => $rate,
                'total_students' => $totalStudents,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @param  Collection<string, AttendanceStatus|null>  $rangeStatuses
     * @return list<array<string, mixed>>
     */
    protected function sectionAttendanceBreakdown(
        Carbon $from,
        Carbon $to,
        array $filters,
        Collection $rangeStatuses,
    ): array {
        $students = $this->activeStudentQuery($filters)
            ->with(['gradeLevel', 'section'])
            ->whereNotNull('section_id')
            ->get()
            ->groupBy('section_id');

        return $students->map(function (Collection $sectionStudents) use ($from, $to, $rangeStatuses) {
            $studentIds = $sectionStudents->pluck('id');
            $total = $studentIds->count();
            $attendedDays = 0;
            $studentDays = 0;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }

                $studentDays += $total;
                $dateString = $date->toDateString();

                foreach ($studentIds as $studentId) {
                    if ($this->attendanceResolver->isAttended($rangeStatuses->get($studentId.'|'.$dateString))) {
                        $attendedDays++;
                    }
                }
            }

            $section = $sectionStudents->first()?->section;
            $grade = $sectionStudents->first()?->gradeLevel;

            return [
                'section' => trim(($grade?->name ?? '').' — '.($section?->name ?? 'Unknown'), ' —'),
                'students' => $total,
                'attended' => $attendedDays,
                'student_days' => $studentDays,
                'rate' => $studentDays > 0 ? round(($attendedDays / $studentDays) * 100, 1) : 0,
            ];
        })
            ->sortByDesc('rate')
            ->values()
            ->take(12)
            ->all();
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return list<array<string, mixed>>
     */
    protected function departmentAttendanceBreakdown(Carbon $focusDate, array $filters): array
    {
        if (($filters['section'] ?? null) || ($filters['grade'] ?? null)) {
            return [];
        }

        $students = Student::query()
            ->with('gradeLevel.department')
            ->where('status', StudentStatus::Active)
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ))
            ->get()
            ->groupBy(fn (Student $student) => $student->gradeLevel?->department_id ?? 0);

        if ($students->isEmpty()) {
            return [];
        }

        $allIds = $students->flatten()->pluck('id');
        $statuses = $this->attendanceResolver->resolveForStudents($focusDate, $allIds);

        return $students->map(function (Collection $departmentStudents) use ($statuses) {
            $total = $departmentStudents->count();
            $attended = $departmentStudents->filter(
                fn (Student $student) => $this->attendanceResolver->isAttended($statuses->get($student->id)),
            )->count();
            $department = $departmentStudents->first()?->gradeLevel?->department;

            return [
                'department' => $department?->name ?? 'Unassigned',
                'students' => $total,
                'attended' => $attended,
                'rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0,
            ];
        })
            ->sortByDesc('rate')
            ->values()
            ->all();
    }

    protected function classesInSessionCount(Carbon $today): int
    {
        $now = now()->format('H:i:s');
        $dayOfWeek = $this->scheduleResolver->dayOfWeekForDate($today->toDateString());

        return ClassSchedule::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->count();
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return Collection<int, Student&{absences_count: int}>
     */
    protected function topAbsentees(Carbon $from, Carbon $to, array $filters): Collection
    {
        $students = $this->activeStudentQuery($filters)
            ->with(['gradeLevel', 'section'])
            ->get();

        $rangeStatuses = $this->attendanceResolver->resolveForDateRange(
            $from,
            $to,
            $students->pluck('id'),
        );

        return $students->map(function (Student $student) use ($from, $to, $rangeStatuses) {
            $absences = 0;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }

                $status = $rangeStatuses->get($student->id.'|'.$date->toDateString());

                if ($this->attendanceResolver->isAbsentDay($status)) {
                    $absences++;
                }
            }

            $student->absences_count = $absences;

            return $student;
        })
            ->filter(fn (Student $student) => $student->absences_count > 0)
            ->sortByDesc('absences_count')
            ->take(5)
            ->values();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    protected function recentActivity(): Collection
    {
        $superAdminRole = UserRole::superAdminValue();

        return AuditLog::query()
            ->with('user')
            ->where(function (Builder $query) use ($superAdminRole) {
                $query->whereNull('user_id')
                    ->orWhereDoesntHave(
                        'user.roles',
                        fn (Builder $roles) => $roles->where('name', $superAdminRole),
                    );
            })
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    /**
     * @param  array{department?: ?int, grade?: ?int, section?: ?int}  $filters
     * @return list<array{title: string, time: string, location: string, status: string, date: Carbon}>
     */
    protected function todaysSchedules(Carbon $today, array $filters = []): array
    {
        $dayOfWeek = $this->scheduleResolver->dayOfWeekForDate($today->toDateString());
        $now = now()->format('H:i:s');
        $isToday = $today->isToday();

        return ClassSchedule::query()
            ->with(['subject', 'section.gradeLevel', 'teacher', 'room'])
            ->where('day_of_week', $dayOfWeek)
            ->when($filters['section'] ?? null, fn (Builder $q, $section) => $q->where('section_id', $section))
            ->when($filters['grade'] ?? null, fn (Builder $q, $grade) => $q->whereHas(
                'section',
                fn (Builder $section) => $section->where('grade_level_id', $grade),
            ))
            ->when($filters['department'] ?? null, fn (Builder $q, $department) => $q->whereHas(
                'section.gradeLevel',
                fn (Builder $gradeLevel) => $gradeLevel->where('department_id', $department),
            ))
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(function (ClassSchedule $schedule) use ($now, $today, $isToday) {
                $starts = substr((string) $schedule->starts_at, 0, 5);
                $ends = substr((string) $schedule->ends_at, 0, 5);
                $status = 'Scheduled';

                if ($isToday) {
                    $status = 'Upcoming';

                    if ($now >= (string) $schedule->starts_at && $now <= (string) $schedule->ends_at) {
                        $status = 'In session';
                    } elseif ($now > (string) $schedule->ends_at) {
                        $status = 'Completed';
                    }
                }

                return [
                    'title' => $schedule->subject?->name ?? 'Class',
                    'time' => "{$starts} – {$ends}",
                    'location' => $schedule->room?->name
                        ?? $schedule->section?->room
                        ?? ($schedule->section ? $schedule->section->gradeLevel?->name.' '.$schedule->section->name : '—'),
                    'status' => $status,
                    'date' => $today,
                ];
            })
            ->all();
    }

    /**
     * @return array{icon: string, bg: string, text: string}
     */
    public function activityStyle(AuditAction|string|null $action): array
    {
        $value = $action instanceof AuditAction ? $action : AuditAction::tryFrom((string) $action);

        return match ($value) {
            AuditAction::Delete => [
                'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
                'bg' => 'bg-red-100 dark:bg-red-900/30',
                'text' => 'text-red-600',
            ],
            AuditAction::Import, AuditAction::Export => [
                'icon' => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'text' => 'text-blue-600',
            ],
            AuditAction::Attendance => [
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
                'text' => 'text-emerald-600',
            ],
            AuditAction::Settings, AuditAction::Backup, AuditAction::Restore => [
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'bg' => 'bg-violet-100 dark:bg-violet-900/30',
                'text' => 'text-violet-600',
            ],
            default => [
                'icon' => 'M12 4v16m8-8H4',
                'bg' => 'bg-slate-100 dark:bg-slate-800',
                'text' => 'text-slate-600',
            ],
        };
    }
}
