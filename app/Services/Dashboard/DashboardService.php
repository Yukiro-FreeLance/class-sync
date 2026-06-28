<?php

namespace App\Services\Dashboard;

use App\Enums\AttendanceStatus;
use App\Enums\AuditAction;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
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
     * @return array<string, mixed>
     */
    public function data(?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $yesterday = $today->copy()->subDay();
        $weekStart = $today->copy()->startOfWeek();

        $activeStudents = Student::query()
            ->where('status', StudentStatus::Active)
            ->pluck('id');

        $totalStudents = $activeStudents->count();
        $todayStatuses = $this->attendanceResolver->resolveForStudents($today, $activeStudents);
        $yesterdayStatuses = $this->attendanceResolver->resolveForStudents($yesterday, $activeStudents);

        $todayRecords = AttendanceRecord::query()
            ->with('student')
            ->whereDate('date', $today->toDateString())
            ->get()
            ->keyBy('student_id');

        $present = $todayStatuses->filter(fn ($status) => $status === AttendanceStatus::Present)->count();
        $late = $todayStatuses->filter(fn ($status) => $status === AttendanceStatus::Late)->count();
        $excused = $todayStatuses->filter(fn ($status) => $status === AttendanceStatus::Excused)->count();
        $explicitAbsent = $todayStatuses->filter(fn ($status) => $status === AttendanceStatus::Absent)->count();
        $halfDay = $todayStatuses->filter(fn ($status) => $status === AttendanceStatus::HalfDay)->count();
        $recordedToday = $todayStatuses->filter(fn ($status) => $status !== null)->count();
        $notRecorded = max(0, $totalStudents - $recordedToday);
        $absent = $explicitAbsent + $halfDay + $notRecorded;

        $attended = $present + $late + $excused;
        $attendancePercent = $totalStudents > 0
            ? round(($attended / $totalStudents) * 100, 1)
            : 0;

        $yesterdayPresent = $yesterdayStatuses->filter(fn ($status) => $status === AttendanceStatus::Present)->count();
        $presentTrend = $yesterdayPresent > 0
            ? round((($present - $yesterdayPresent) / $yesterdayPresent) * 100, 1)
            : ($present > 0 ? 100 : 0);

        $pct = fn (int $value): float => $totalStudents > 0 ? round(($value / $totalStudents) * 100, 1) : 0;

        $weeklyLabels = collect(range(6, 0))->map(fn ($d) => $today->copy()->subDays($d)->format('D'));
        $weekRangeStatuses = $this->attendanceResolver->resolveForDateRange(
            $today->copy()->subDays(6),
            $today,
            $activeStudents,
        );
        $weeklyData = collect(range(6, 0))->map(function ($d) use ($today, $totalStudents, $activeStudents, $weekRangeStatuses) {
            if ($totalStudents === 0) {
                return 0;
            }

            $dateString = $today->copy()->subDays($d)->toDateString();
            $attendedCount = $activeStudents->filter(function (int $studentId) use ($dateString, $weekRangeStatuses) {
                $status = $weekRangeStatuses->get($studentId.'|'.$dateString);

                return $this->attendanceResolver->isAttended($status);
            })->count();

            return (int) round(($attendedCount / $totalStudents) * 100);
        });

        $statusBreakdown = [
            'Present' => $present,
            'Late' => $late,
            'Excused' => $excused,
            'Absent' => $explicitAbsent + $halfDay,
            'Not recorded' => $notRecorded,
        ];

        $classAbsencesToday = AttendancePeriodLog::query()
            ->whereDate('date', $today->toDateString())
            ->whereHas('remark', fn ($q) => $q->where('counts_as_present', false))
            ->count();

        $topAbsentees = $this->topAbsentees($weekStart, $today);
        $maxAbsences = $topAbsentees->max('absences_count') ?: 1;

        return [
            'stats' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'explicit_absent' => $explicitAbsent,
                'not_recorded' => $notRecorded,
                'excused' => $excused,
                'visitors' => Visitor::query()->whereDate('time_in', $today->toDateString())->count(),
                'checkouts' => $todayRecords->whereNotNull('time_out')->count(),
                'attendance_percent' => $attendancePercent,
                'total_students' => $totalStudents,
                'recorded_today' => $recordedToday,
                'present_pct' => $pct($present),
                'late_pct' => $pct($late),
                'absent_pct' => $pct($absent),
                'excused_pct' => $pct($excused),
                'present_trend' => $presentTrend,
            ],
            'liveOverview' => [
                'inside_campus' => $this->attendanceService->getStudentsInsideCampus($today)->count(),
                'classes_in_session' => $this->classesInSessionCount($today),
                'active_alerts' => $explicitAbsent + $classAbsencesToday,
                'pending_approvals' => StudentEnrollment::query()
                    ->where('status', EnrollmentStatus::Pending)
                    ->count(),
            ],
            'weeklyLabels' => $weeklyLabels->values()->all(),
            'weeklyData' => $weeklyData->values()->all(),
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
            'todaysSchedules' => $this->todaysSchedules($today),
            'recentCheckIns' => $this->attendanceService->getAttendanceForDate($today)
                ->whereNotNull('time_in')
                ->sortByDesc('time_in')
                ->take(5),
        ];
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
     * @return Collection<int, Student&{absences_count: int}>
     */
    protected function topAbsentees(Carbon $weekStart, Carbon $today): Collection
    {
        $students = Student::query()
            ->with(['gradeLevel', 'section'])
            ->where('status', StudentStatus::Active)
            ->get();

        $rangeStatuses = $this->attendanceResolver->resolveForDateRange(
            $weekStart,
            $today,
            $students->pluck('id'),
        );

        return $students->map(function (Student $student) use ($weekStart, $today, $rangeStatuses) {
            $absences = 0;

            for ($date = $weekStart->copy(); $date->lte($today); $date->addDay()) {
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
        return AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return list<array{title: string, time: string, location: string, status: string, date: Carbon}>
     */
    protected function todaysSchedules(Carbon $today): array
    {
        $dayOfWeek = $this->scheduleResolver->dayOfWeekForDate($today->toDateString());
        $now = now()->format('H:i:s');

        return ClassSchedule::query()
            ->with(['subject', 'section.gradeLevel', 'teacher', 'room'])
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(function (ClassSchedule $schedule) use ($now, $today) {
                $starts = substr((string) $schedule->starts_at, 0, 5);
                $ends = substr((string) $schedule->ends_at, 0, 5);
                $status = 'Upcoming';

                if ($now >= (string) $schedule->starts_at && $now <= (string) $schedule->ends_at) {
                    $status = 'In session';
                } elseif ($now > (string) $schedule->ends_at) {
                    $status = 'Completed';
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
