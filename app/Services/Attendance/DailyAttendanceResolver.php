<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyAttendanceResolver
{
    /**
     * Higher score = worse attendance outcome for the day.
     * Dashboard daily status keeps the most severe mark across gate + class periods
     * so an Absent is not upgraded to Excused/Present.
     *
     * @var array<string, int>
     */
    private const STATUS_SEVERITY = [
        AttendanceStatus::Absent->value => 5,
        AttendanceStatus::HalfDay->value => 4,
        AttendanceStatus::Excused->value => 3,
        AttendanceStatus::Late->value => 2,
        AttendanceStatus::Present->value => 1,
    ];

    public function remarkCodeToStatus(?string $code): ?AttendanceStatus
    {
        return AttendanceStatus::tryFrom((string) $code);
    }

    /**
     * Keep the worse status when both gate and class marks exist.
     */
    public function mergeStatuses(?AttendanceStatus $gate, ?AttendanceStatus $class): ?AttendanceStatus
    {
        if ($gate === null) {
            return $class;
        }

        if ($class === null) {
            return $gate;
        }

        return $this->worseStatus($gate, $class);
    }

    /**
     * @param  iterable<int, AttendanceRemark|null>  $remarks
     */
    public function bestStatusFromRemarks(iterable $remarks): ?AttendanceStatus
    {
        return $this->worstStatusFromRemarks($remarks);
    }

    /**
     * @param  iterable<int, AttendanceRemark|null>  $remarks
     */
    public function worstStatusFromRemarks(iterable $remarks): ?AttendanceStatus
    {
        $worst = null;
        $worstSeverity = 0;

        foreach ($remarks as $remark) {
            $status = $this->remarkCodeToStatus($remark?->code);

            if (! $status) {
                continue;
            }

            $severity = self::STATUS_SEVERITY[$status->value] ?? 0;

            if ($severity > $worstSeverity) {
                $worstSeverity = $severity;
                $worst = $status;
            }
        }

        return $worst;
    }

    public function worseStatus(AttendanceStatus $left, AttendanceStatus $right): AttendanceStatus
    {
        $leftSeverity = self::STATUS_SEVERITY[$left->value] ?? 0;
        $rightSeverity = self::STATUS_SEVERITY[$right->value] ?? 0;

        return $rightSeverity > $leftSeverity ? $right : $left;
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @return Collection<int, AttendanceStatus|null> keyed by student_id
     */
    public function resolveForStudents(Carbon|string $date, ?Collection $studentIds = null): Collection
    {
        $dateString = Carbon::parse($date)->toDateString();

        $gateRecords = AttendanceRecord::query()
            ->whereDate('date', $dateString)
            ->when($studentIds, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->get()
            ->keyBy('student_id');

        $classLogs = AttendancePeriodLog::query()
            ->with('remark')
            ->whereDate('date', $dateString)
            ->when($studentIds, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->get()
            ->groupBy('student_id');

        $ids = $studentIds
            ?? $gateRecords->keys()->merge($classLogs->keys())->unique()->values();

        return $ids->mapWithKeys(function (int $studentId) use ($gateRecords, $classLogs) {
            $gateStatus = $gateRecords->get($studentId)?->status;
            $classStatus = $this->worstStatusFromRemarks(
                $classLogs->get($studentId, collect())->pluck('remark'),
            );

            return [$studentId => $this->mergeStatuses($gateStatus, $classStatus)];
        });
    }

    public function isAbsentDay(?AttendanceStatus $status): bool
    {
        return $status === null
            || $status === AttendanceStatus::Absent
            || $status === AttendanceStatus::HalfDay;
    }

    public function isAttended(?AttendanceStatus $status): bool
    {
        return in_array($status, [
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::Excused,
        ], true);
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @return Collection<string, AttendanceStatus|null> keyed by "{student_id}|{date}"
     */
    public function resolveForDateRange(
        Carbon|string $from,
        Carbon|string $to,
        ?Collection $studentIds = null,
    ): Collection {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $gateRecords = AttendanceRecord::query()
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->when($studentIds, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->student_id.'|'.$record->date->toDateString());

        $classLogs = AttendancePeriodLog::query()
            ->with('remark')
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->when($studentIds, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->get()
            ->groupBy(fn (AttendancePeriodLog $log) => $log->student_id.'|'.$log->date->toDateString());

        $keys = $gateRecords->keys()->merge($classLogs->keys())->unique();

        if ($studentIds) {
            $cursor = Carbon::parse($fromDate);
            $end = Carbon::parse($toDate);

            while ($cursor->lte($end)) {
                foreach ($studentIds as $studentId) {
                    $keys->push($studentId.'|'.$cursor->toDateString());
                }

                $cursor->addDay();
            }

            $keys = $keys->unique()->values();
        }

        return $keys->mapWithKeys(function (string $key) use ($gateRecords, $classLogs) {
            $gateStatus = $gateRecords->get($key)?->first()?->status;
            $classStatus = $this->worstStatusFromRemarks(
                $classLogs->get($key, collect())->pluck('remark'),
            );

            return [$key => $this->mergeStatuses($gateStatus, $classStatus)];
        });
    }
}
