<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\Visitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LiveMonitorService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected ClassScheduleResolver $scheduleResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?Carbon $at = null): array
    {
        $at ??= now();
        $today = $at->copy()->startOfDay();
        $todayString = $today->toDateString();

        $totalStudents = Student::query()->where('status', StudentStatus::Active)->count();

        $todayRecords = AttendanceRecord::query()
            ->with(['student.gradeLevel', 'student.section.course'])
            ->whereDate('date', $todayString)
            ->get();

        $present = $todayRecords->where('status', AttendanceStatus::Present)->count();
        $late = $todayRecords->where('status', AttendanceStatus::Late)->count();
        $excused = $todayRecords->where('status', AttendanceStatus::Excused)->count();
        $explicitAbsent = $todayRecords->where('status', AttendanceStatus::Absent)->count();
        $notRecorded = max(0, $totalStudents - $todayRecords->count());
        $absent = $explicitAbsent + $notRecorded;
        $checkouts = $todayRecords->whereNotNull('time_out')->count();

        $inside = $this->attendanceService->getStudentsInsideCampus($today)
            ->load(['student.gradeLevel', 'student.section.course'])
            ->map(fn (AttendanceRecord $record) => $this->formatInsideRecord($record, $at));

        $recentCheckIns = $todayRecords
            ->filter(fn ($record) => $record->time_in !== null)
            ->sortByDesc('time_in')
            ->take(20)
            ->values()
            ->map(fn (AttendanceRecord $record) => $this->formatCheckInRecord($record));

        $recentCheckOuts = $todayRecords
            ->filter(fn ($record) => $record->time_out !== null)
            ->sortByDesc('time_out')
            ->take(15)
            ->values()
            ->map(fn (AttendanceRecord $record) => $this->formatCheckOutRecord($record));

        $notCheckedIn = $this->notCheckedInStudents($todayRecords, 12);

        $classesInSession = $this->activeClasses($at);
        $upcomingClasses = $this->upcomingClasses($at, 5);

        $visitorsInside = Visitor::query()->whereNull('time_out')->whereDate('time_in', $todayString)->count();
        $classAlerts = AttendancePeriodLog::query()
            ->whereDate('date', $todayString)
            ->whereHas('remark', fn ($q) => $q->where('counts_as_present', false))
            ->count();

        $pct = fn (int $value): float => $totalStudents > 0 ? round(($value / $totalStudents) * 100, 1) : 0;

        return [
            'stats' => [
                'inside_campus' => $inside->count(),
                'present' => $present,
                'late' => $late,
                'excused' => $excused,
                'absent' => $absent,
                'explicit_absent' => $explicitAbsent,
                'not_recorded' => $notRecorded,
                'checkouts' => $checkouts,
                'visitors_inside' => $visitorsInside,
                'total_students' => $totalStudents,
                'recorded_today' => $todayRecords->count(),
                'present_pct' => $pct($present),
                'late_pct' => $pct($late),
                'absent_pct' => $pct($absent),
                'attendance_rate' => $totalStudents > 0
                    ? round((($present + $late + $excused) / $totalStudents) * 100, 1)
                    : 0,
            ],
            'alerts' => [
                'class_absences' => $classAlerts,
                'not_checked_in' => $notRecorded,
            ],
            'inside' => $inside,
            'recentCheckIns' => $recentCheckIns,
            'recentCheckOuts' => $recentCheckOuts,
            'notCheckedIn' => $notCheckedIn,
            'classesInSession' => $classesInSession,
            'upcomingClasses' => $upcomingClasses,
            'lastUpdated' => $at->format('g:i:s A'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatInsideRecord(AttendanceRecord $record, Carbon $at): array
    {
        $timeIn = $record->time_in ? Carbon::parse($record->date->toDateString().' '.$record->time_in) : null;
        $minutesInside = $timeIn ? $timeIn->diffInMinutes($at) : null;

        return [
            'record' => $record,
            'name' => $record->student?->list_name ?? 'Unknown',
            'student_number' => $record->student?->student_number,
            'grade' => $record->student?->gradeLevel?->name,
            'section' => $record->student?->section?->display_label,
            'time_in' => $record->time_in ? Str::substr((string) $record->time_in, 0, 5) : '—',
            'duration' => $minutesInside !== null ? $this->humanDuration($minutesInside) : '—',
            'status' => $record->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatCheckInRecord(AttendanceRecord $record): array
    {
        return [
            'record' => $record,
            'name' => $record->student?->list_name ?? 'Unknown',
            'student_number' => $record->student?->student_number,
            'grade' => $record->student?->gradeLevel?->name,
            'section' => $record->student?->section?->display_label,
            'time' => $record->time_in ? Str::substr((string) $record->time_in, 0, 5) : '—',
            'status' => $record->status,
            'method' => $record->method?->label() ?? 'Manual',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatCheckOutRecord(AttendanceRecord $record): array
    {
        return [
            'record' => $record,
            'name' => $record->student?->list_name ?? 'Unknown',
            'student_number' => $record->student?->student_number,
            'time_in' => $record->time_in ? Str::substr((string) $record->time_in, 0, 5) : '—',
            'time_out' => $record->time_out ? Str::substr((string) $record->time_out, 0, 5) : '—',
        ];
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $todayRecords
     * @return Collection<int, array<string, mixed>>
     */
    protected function notCheckedInStudents(Collection $todayRecords, int $limit): Collection
    {
        $recordedIds = $todayRecords->pluck('student_id');

        return Student::query()
            ->with(['gradeLevel', 'section.course'])
            ->where('status', StudentStatus::Active)
            ->when($recordedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $recordedIds))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->limit($limit)
            ->get()
            ->map(fn (Student $student) => [
                'name' => $student->list_name,
                'student_number' => $student->student_number,
                'grade' => $student->gradeLevel?->name,
                'section' => $student->section?->display_label,
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activeClasses(Carbon $at): array
    {
        $now = $at->format('H:i:s');
        $dayOfWeek = $this->scheduleResolver->dayOfWeekForDate($at->toDateString());

        return ClassSchedule::query()
            ->with(['subject', 'section.gradeLevel', 'section.course', 'teacher', 'room'])
            ->where('day_of_week', $dayOfWeek)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ClassSchedule $schedule) => $this->formatClassRow($schedule, 'In session'))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function upcomingClasses(Carbon $at, int $limit): array
    {
        $now = $at->format('H:i:s');
        $dayOfWeek = $this->scheduleResolver->dayOfWeekForDate($at->toDateString());

        return ClassSchedule::query()
            ->with(['subject', 'section.gradeLevel', 'section.course', 'teacher', 'room'])
            ->where('day_of_week', $dayOfWeek)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (ClassSchedule $schedule) => $this->formatClassRow($schedule, 'Upcoming'))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatClassRow(ClassSchedule $schedule, string $status): array
    {
        $starts = Str::substr((string) $schedule->starts_at, 0, 5);
        $ends = Str::substr((string) $schedule->ends_at, 0, 5);

        return [
            'subject' => $schedule->subject?->name ?? 'Class',
            'section' => $schedule->section?->display_label ?? '—',
            'teacher' => $schedule->teacher?->name ?? '—',
            'room' => $schedule->room?->name ?? $schedule->section?->room ?? '—',
            'time' => "{$starts} – {$ends}",
            'status' => $status,
        ];
    }

    protected function humanDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
    }
}
