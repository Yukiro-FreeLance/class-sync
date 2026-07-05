<?php

namespace App\Services\Academic;

use App\Models\ClassSchedule;
use Illuminate\Support\Collection;

class ClassScheduleConflictService
{
    public function timesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $startA = $this->normalizeTime($startA);
        $endA = $this->normalizeTime($endA);
        $startB = $this->normalizeTime($startB);
        $endB = $this->normalizeTime($endB);

        return $startA < $endB && $startB < $endA;
    }

    /**
     * @return list<array{type: string, message: string, schedule_id: int, day: int}>
     */
    public function findConflictsForSlot(
        int $academicYearId,
        int $sectionId,
        int $subjectId,
        int $teacherId,
        ?int $roomId,
        string $semester,
        int $dayOfWeek,
        string $startsAt,
        string $endsAt,
        ?int $ignoreScheduleId = null,
    ): array {
        $startsAt = $this->normalizeTime($startsAt);
        $endsAt = $this->normalizeTime($endsAt);

        $existing = ClassSchedule::query()
            ->with(['subject', 'section.gradeLevel', 'teacher', 'room'])
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('day_of_week', $dayOfWeek)
            ->when($ignoreScheduleId, fn ($query) => $query->where('id', '!=', $ignoreScheduleId))
            ->get()
            ->filter(fn (ClassSchedule $schedule) => ! (
                $schedule->section_id === $sectionId
                && $schedule->subject_id === $subjectId
            ))
            ->filter(fn (ClassSchedule $schedule) => $this->timesOverlap(
                $startsAt,
                $endsAt,
                (string) $schedule->starts_at,
                (string) $schedule->ends_at,
            ));

        $conflicts = [];

        foreach ($existing as $schedule) {
            $dayLabel = $schedule->day_of_week->label();
            $timeRange = $schedule->time_range;
            $sectionLabel = trim(($schedule->section?->gradeLevel?->name ?? '').' '.($schedule->section?->name ?? ''));
            $subjectCode = $schedule->subject?->code ?? 'Class';

            if ($schedule->section_id === $sectionId) {
                $conflicts[] = [
                    'type' => 'section',
                    'message' => "{$sectionLabel} already has {$subjectCode} ({$timeRange}) on {$dayLabel}.",
                    'schedule_id' => $schedule->id,
                    'day' => $dayOfWeek,
                ];
            }

            if ($schedule->teacher_id === $teacherId) {
                $teacherName = $schedule->teacher?->full_name ?? 'Teacher';
                $conflicts[] = [
                    'type' => 'teacher',
                    'message' => "{$teacherName} is already teaching {$subjectCode} in {$sectionLabel} ({$timeRange}) on {$dayLabel}.",
                    'schedule_id' => $schedule->id,
                    'day' => $dayOfWeek,
                ];
            }

            if ($roomId && $schedule->room_id === $roomId) {
                $roomName = $schedule->room?->display_name ?? 'Room';
                $conflicts[] = [
                    'type' => 'room',
                    'message' => "{$roomName} is already used for {$subjectCode} in {$sectionLabel} ({$timeRange}) on {$dayLabel}.",
                    'schedule_id' => $schedule->id,
                    'day' => $dayOfWeek,
                ];
            }
        }

        return $this->uniqueConflicts($conflicts);
    }

    public function findConflictsForFormEntries(
        int $academicYearId,
        int $sectionId,
        int $subjectId,
        int $teacherId,
        ?int $roomId,
        string $semester,
        iterable $entries,
        ?int $ignoreScheduleId = null,
    ): array {
        $conflicts = [];

        foreach ($entries as $entry) {
            $conflicts = array_merge(
                $conflicts,
                $this->findConflictsForSlot(
                    $academicYearId,
                    $sectionId,
                    $subjectId,
                    $teacherId,
                    $roomId,
                    $semester,
                    (int) $entry['day'],
                    $entry['starts_at'],
                    $entry['ends_at'],
                    $ignoreScheduleId,
                ),
            );
        }

        return $this->uniqueConflicts($conflicts);
    }

    /**
     * @param  iterable<int, array{enabled: bool, starts_at?: string, ends_at?: string, times?: list<array{starts_at: string, ends_at: string}>}>  $daySlots
     * @return list<array{type: string, message: string, schedule_id: int, day: int}>
     */
    public function findConflictsForForm(
        int $academicYearId,
        int $sectionId,
        int $subjectId,
        int $teacherId,
        ?int $roomId,
        string $semester,
        iterable $daySlots,
        ?int $ignoreScheduleId = null,
    ): array {
        $entries = [];

        foreach ($daySlots as $day => $slot) {
            if (! ($slot['enabled'] ?? false)) {
                continue;
            }

            if (isset($slot['times']) && is_array($slot['times'])) {
                foreach ($slot['times'] as $time) {
                    $entries[] = [
                        'day' => (int) $day,
                        'starts_at' => $time['starts_at'],
                        'ends_at' => $time['ends_at'],
                    ];
                }

                continue;
            }

            if (($slot['starts_at'] ?? '') !== '' && ($slot['ends_at'] ?? '') !== '') {
                $entries[] = [
                    'day' => (int) $day,
                    'starts_at' => $slot['starts_at'],
                    'ends_at' => $slot['ends_at'],
                ];
            }
        }

        return $this->findConflictsForFormEntries(
            $academicYearId,
            $sectionId,
            $subjectId,
            $teacherId,
            $roomId,
            $semester,
            $entries,
            $ignoreScheduleId,
        );
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return array{ids: array<int, true>, details: array<int, list<string>>}
     */
    public function analyzeCollection(Collection $schedules): array
    {
        $ids = [];
        $details = [];

        foreach ($schedules as $schedule) {
            $messages = $this->conflictMessagesForSchedule($schedule, $schedules);

            if ($messages === []) {
                continue;
            }

            $ids[$schedule->id] = true;
            $details[$schedule->id] = $messages;

            foreach ($schedules as $other) {
                if ($other->id === $schedule->id) {
                    continue;
                }

                if ($this->hasConflictBetween($schedule, $other)) {
                    $ids[$other->id] = true;
                }
            }
        }

        return ['ids' => $ids, 'details' => $details];
    }

    public function hasConflictBetween(ClassSchedule $a, ClassSchedule $b): bool
    {
        if ($a->academic_year_id !== $b->academic_year_id
            || $a->semester !== $b->semester
            || $a->day_of_week !== $b->day_of_week) {
            return false;
        }

        if (! $this->timesOverlap(
            (string) $a->starts_at,
            (string) $a->ends_at,
            (string) $b->starts_at,
            (string) $b->ends_at,
        )) {
            return false;
        }

        if ($a->section_id === $b->section_id) {
            return true;
        }

        if ($a->teacher_id === $b->teacher_id) {
            return true;
        }

        if ($a->room_id && $b->room_id === $a->room_id) {
            return true;
        }

        return false;
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return list<string>
     */
    public function conflictMessagesForSchedule(ClassSchedule $schedule, Collection $schedules): array
    {
        $messages = [];

        foreach ($schedules as $other) {
            if ($other->id === $schedule->id) {
                continue;
            }

            if ($other->academic_year_id !== $schedule->academic_year_id
                || $other->semester !== $schedule->semester
                || $other->day_of_week !== $schedule->day_of_week) {
                continue;
            }

            if (! $this->timesOverlap(
                (string) $schedule->starts_at,
                (string) $schedule->ends_at,
                (string) $other->starts_at,
                (string) $other->ends_at,
            )) {
                continue;
            }

            if ($other->section_id === $schedule->section_id) {
                $messages[] = "Section conflict with {$other->subject?->code} at {$other->time_range}.";
            }

            if ($other->teacher_id === $schedule->teacher_id) {
                $sectionName = trim(($other->section?->gradeLevel?->name ?? '').' '.($other->section?->name ?? ''));
                $messages[] = "Teacher conflict with {$other->subject?->code} in {$sectionName}.";
            }

            if ($schedule->room_id && $other->room_id === $schedule->room_id) {
                $messages[] = "Room conflict with {$other->subject?->code} at {$other->time_range}.";
            }
        }

        return array_values(array_unique($messages));
    }

    /**
     * @param  list<array{type: string, message: string, schedule_id: int, day: int}>  $conflicts
     * @return list<array{type: string, message: string, schedule_id: int, day: int}>
     */
    protected function uniqueConflicts(array $conflicts): array
    {
        $seen = [];
        $unique = [];

        foreach ($conflicts as $conflict) {
            $key = $conflict['type'].'|'.$conflict['schedule_id'].'|'.$conflict['day'].'|'.$conflict['message'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $conflict;
        }

        return $unique;
    }

    protected function normalizeTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
