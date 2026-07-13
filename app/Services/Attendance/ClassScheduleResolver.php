<?php

namespace App\Services\Attendance;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClassScheduleResolver
{
    public function dayOfWeekForDate(string $date): int
    {
        return Carbon::parse($date)->dayOfWeekIso;
    }

    /**
     * @return Collection<int, ClassSchedule>
     */
    public function forSectionOnDate(int $sectionId, string $date, ?int $academicYearId = null): Collection
    {
        $academicYearId ??= AcademicYear::query()->where('is_current', true)->value('id');

        return ClassSchedule::query()
            ->with(['subject', 'teacher', 'room', 'section'])
            ->where('section_id', $sectionId)
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->where('day_of_week', $this->dayOfWeekForDate($date))
            ->orderBy('starts_at')
            ->get();
    }

    public function findForSectionOnDate(int $sectionId, int $classScheduleId, string $date): ?ClassSchedule
    {
        return $this->forSectionOnDate($sectionId, $date)
            ->firstWhere('id', $classScheduleId);
    }

    public function suggestCurrent(int $sectionId, string $date, ?Carbon $at = null): ?ClassSchedule
    {
        $schedules = $this->forSectionOnDate($sectionId, $date);

        if ($schedules->isEmpty()) {
            return null;
        }

        $at ??= now();
        $time = $at->format('H:i:s');

        $active = $schedules->first(
            fn (ClassSchedule $schedule) => $time >= (string) $schedule->starts_at && $time <= (string) $schedule->ends_at,
        );

        if ($active) {
            return $active;
        }

        $upcoming = $schedules->first(fn (ClassSchedule $schedule) => (string) $schedule->starts_at > $time);

        return $upcoming ?? $schedules->last();
    }

    /**
     * @return Collection<int, Student>
     */
    public function studentsForSchedule(int $sectionId, int $classScheduleId): Collection
    {
        $enrolled = Student::query()
            ->where('section_id', $sectionId)
            ->whereHas(
                'enrollments',
                fn ($query) => $query->whereHas(
                    'classSchedules',
                    fn ($scheduleQuery) => $scheduleQuery->where('class_schedules.id', $classScheduleId),
                ),
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->get();

        if ($enrolled->isNotEmpty()) {
            return $enrolled;
        }

        return Student::query()
            ->where('section_id', $sectionId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->get();
    }
}
