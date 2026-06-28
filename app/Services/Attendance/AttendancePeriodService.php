<?php

namespace App\Services\Attendance;

use App\Enums\AttendancePeriodEventType;
use App\Models\AttendancePeriodEvent;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRemark;
use App\Models\ClassPeriod;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\TeacherScopeService;
use App\Services\Audit\AuditLogService;
use Database\Seeders\AttendanceRemarkSeeder;
use Database\Seeders\ClassPeriodSeeder;
use Illuminate\Support\Collection;

class AttendancePeriodService
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected ClassScheduleResolver $scheduleResolver,
    ) {}

    public function ensureDefaults(): void
    {
        if (AttendanceRemark::query()->doesntExist()) {
            (new AttendanceRemarkSeeder)->run();
        }

        if (ClassPeriod::query()->doesntExist()) {
            (new ClassPeriodSeeder)->run();
        }
    }

    public function defaultRemarkId(): int
    {
        $this->ensureDefaults();

        return AttendanceRemark::query()
            ->where('is_default', true)
            ->value('id')
            ?? AttendanceRemark::query()->active()->ordered()->value('id');
    }

    /**
     * @param  array<int, array{remark_id: int, remarks?: ?string, went_out?: bool, went_out_at?: ?string}>  $entries
     * @return array{saved: int, errors: list<string>}
     */
    public function bulkSave(
        int $sectionId,
        int $classScheduleId,
        string $date,
        array $entries,
        ?int $userId = null,
    ): array {
        $user = $userId ? User::query()->find($userId) : auth()->user();

        if ($user) {
            app(TeacherScopeService::class)
                ->authorizeClassAttendance($user, $classScheduleId, $sectionId, $date);
        }

        $saved = 0;
        $errors = [];

        $schedule = $this->scheduleResolver->findForSectionOnDate($sectionId, $classScheduleId, $date);

        if (! $schedule) {
            return [
                'saved' => 0,
                'errors' => ['The selected class is not scheduled for this section on the chosen date.'],
            ];
        }

        foreach ($entries as $studentId => $entry) {
            try {
                $this->saveClassLog(
                    studentId: (int) $studentId,
                    classScheduleId: $classScheduleId,
                    sectionId: $sectionId,
                    date: $date,
                    remarkId: (int) $entry['remark_id'],
                    remarks: $entry['remarks'] ?? null,
                    wentOut: (bool) ($entry['went_out'] ?? false),
                    wentOutAt: $entry['went_out_at'] ?? null,
                    userId: $userId,
                );
                $saved++;
            } catch (\Throwable $e) {
                $errors[] = "Student #{$studentId}: {$e->getMessage()}";
            }
        }

        return ['saved' => $saved, 'errors' => $errors];
    }

    public function saveClassLog(
        int $studentId,
        int $classScheduleId,
        ?int $sectionId,
        string $date,
        int $remarkId,
        ?string $remarks = null,
        bool $wentOut = false,
        ?string $wentOutAt = null,
        ?int $userId = null,
    ): AttendancePeriodLog {
        $user = $userId ? User::query()->find($userId) : auth()->user();

        if ($user && $sectionId) {
            app(TeacherScopeService::class)
                ->authorizeClassAttendance($user, $classScheduleId, $sectionId, $date);
        }

        $existing = AttendancePeriodLog::query()
            ->where('student_id', $studentId)
            ->where('class_schedule_id', $classScheduleId)
            ->whereDate('date', $date)
            ->first();

        $wentOutTime = $wentOut ? ($wentOutAt ?? now()->format('H:i:s')) : null;

        $log = AttendancePeriodLog::query()->updateOrCreate(
            [
                'student_id' => $studentId,
                'class_schedule_id' => $classScheduleId,
                'date' => $date,
            ],
            [
                'class_period_id' => null,
                'section_id' => $sectionId,
                'attendance_remark_id' => $remarkId,
                'remarks' => $remarks,
                'went_out_at' => $wentOutTime,
                'returned_at' => $wentOut ? $existing?->returned_at : null,
                'user_id' => $userId,
            ],
        );

        if ($wentOut && $wentOutTime && ! $existing?->went_out_at) {
            $this->recordEvent($log, AttendancePeriodEventType::Out, $remarks, $userId);
        }

        return $log->load(['remark', 'classSchedule.subject', 'classSchedule.teacher', 'events']);
    }

    public function recordReturn(AttendancePeriodLog $log, ?string $remarks = null, ?int $userId = null): AttendancePeriodLog
    {
        $log->update([
            'returned_at' => now()->format('H:i:s'),
        ]);

        $this->recordEvent($log, AttendancePeriodEventType::Return, $remarks, $userId);

        return $log->fresh(['remark', 'classSchedule.subject', 'classPeriod', 'events']);
    }

    public function recordEvent(
        AttendancePeriodLog $log,
        AttendancePeriodEventType $type,
        ?string $remarks = null,
        ?int $userId = null,
    ): AttendancePeriodEvent {
        return AttendancePeriodEvent::query()->create([
            'attendance_period_log_id' => $log->id,
            'event_type' => $type->value,
            'remarks' => $remarks,
            'recorded_at' => now(),
            'user_id' => $userId,
        ]);
    }

    /**
     * @return Collection<int, AttendancePeriodLog>
     */
    public function logsForSchedule(int $sectionId, int $classScheduleId, string $date): Collection
    {
        return AttendancePeriodLog::query()
            ->with(['student', 'remark', 'events', 'classSchedule.subject'])
            ->where('section_id', $sectionId)
            ->where('class_schedule_id', $classScheduleId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');
    }

    public function logForStudent(int $studentId, int $classScheduleId, string $date): ?AttendancePeriodLog
    {
        return AttendancePeriodLog::query()
            ->with(['remark', 'events', 'classSchedule.subject'])
            ->where('student_id', $studentId)
            ->where('class_schedule_id', $classScheduleId)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * @return list<array{type: string, at: string, title: string, description: ?string, color: ?string}>
     */
    public function buildStudentTimeline(int $studentId, int $limit = 50): array
    {
        $entries = [];

        $gateRecords = Student::query()->find($studentId)?->attendanceRecords()
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->limit($limit)
            ->get() ?? collect();

        foreach ($gateRecords as $record) {
            if ($record->time_in) {
                $entries[] = [
                    'type' => 'gate_in',
                    'at' => $record->date->format('Y-m-d').' '.$record->time_in,
                    'title' => 'Campus check-in',
                    'description' => $record->status?->label().($record->remarks ? " — {$record->remarks}" : ''),
                    'color' => '#10b981',
                ];
            }
            if ($record->time_out) {
                $entries[] = [
                    'type' => 'gate_out',
                    'at' => $record->date->format('Y-m-d').' '.$record->time_out,
                    'title' => 'Campus check-out',
                    'description' => $record->remarks,
                    'color' => '#64748b',
                ];
            }
        }

        $periodLogs = AttendancePeriodLog::query()
            ->with(['remark', 'classPeriod', 'classSchedule.subject', 'classSchedule.teacher', 'events'])
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->orderByDesc('class_schedule_id')
            ->orderByDesc('class_period_id')
            ->limit($limit)
            ->get();

        foreach ($periodLogs as $log) {
            $classLabel = $log->classSchedule?->display_label
                ?? $log->classSchedule?->subject?->name
                ?? $log->classPeriod?->name
                ?? 'Class';

            $startsAt = $log->classSchedule?->starts_at ?? $log->classPeriod?->starts_at ?? '00:00:00';

            $entries[] = [
                'type' => 'period',
                'at' => $log->date->format('Y-m-d').' '.substr((string) $startsAt, 0, 8),
                'title' => "{$classLabel}: {$log->remark?->label}",
                'description' => collect([
                    $log->remarks,
                    $log->went_out_at ? 'Left at '.substr((string) $log->went_out_at, 0, 5) : null,
                    $log->returned_at ? 'Returned at '.substr((string) $log->returned_at, 0, 5) : null,
                ])->filter()->implode(' · '),
                'color' => $log->remark?->color,
            ];

            foreach ($log->events as $event) {
                $entries[] = [
                    'type' => 'period_event',
                    'at' => $event->recorded_at->toDateTimeString(),
                    'title' => AttendancePeriodEventType::from($event->event_type)->label(),
                    'description' => $event->remarks,
                    'color' => $event->event_type === 'out' ? '#f59e0b' : '#10b981',
                ];
            }
        }

        usort($entries, fn ($a, $b) => strcmp($b['at'], $a['at']));

        return array_slice($entries, 0, $limit);
    }
}
