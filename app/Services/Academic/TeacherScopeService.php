<?php

namespace App\Services\Academic;

use App\Enums\UserRole;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\ClassScheduleResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeacherScopeService
{
    public function __construct(
        protected ClassScheduleResolver $scheduleResolver,
    ) {}

    public function bypassesScope(User $user): bool
    {
        if ($user->acts_as_teacher && $user->hasUnrestrictedAccess()) {
            return false;
        }

        return $this->hasAttendanceBypassRole($user);
    }

    public function bypassesAttendanceScope(User $user): bool
    {
        return $this->hasAttendanceBypassRole($user);
    }

    public function isAttendanceScoped(User $user): bool
    {
        return $user->canActAsTeacher() && ! $this->bypassesAttendanceScope($user);
    }

    /**
     * @return list<int>
     */
    public function accessibleSectionIds(User $user): array
    {
        if ($this->bypassesScope($user)) {
            return Section::query()->pluck('id')->all();
        }

        if (! $user->canActAsTeacher()) {
            return [];
        }

        $fromSchedules = ClassSchedule::query()
            ->where('teacher_id', $user->id)
            ->pluck('section_id');

        $fromAdvisory = Section::query()
            ->where('adviser_id', $user->id)
            ->pluck('id');

        return $fromSchedules->merge($fromAdvisory)->unique()->values()->all();
    }

    /**
     * Sections where the teacher has assigned class schedules.
     *
     * @return list<int>
     */
    public function accessibleScheduleSectionIds(User $user): array
    {
        if ($this->bypassesAttendanceScope($user)) {
            return Section::query()->pluck('id')->all();
        }

        if (! $user->canActAsTeacher()) {
            return [];
        }

        return ClassSchedule::query()
            ->where('teacher_id', $user->id)
            ->pluck('section_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ClassSchedule>
     */
    public function accessibleClassSchedules(User $user, int $sectionId, string $date): Collection
    {
        $schedules = $this->scheduleResolver->forSectionOnDate($sectionId, $date);

        if ($this->bypassesAttendanceScope($user)) {
            return $schedules;
        }

        return $schedules
            ->where('teacher_id', $user->id)
            ->values();
    }

    public function canAccessSection(User $user, int $sectionId): bool
    {
        if ($this->bypassesAttendanceScope($user)) {
            return true;
        }

        return in_array($sectionId, $this->accessibleScheduleSectionIds($user), true);
    }

    public function canAccessClassSchedule(User $user, int $classScheduleId, int $sectionId, string $date): bool
    {
        if ($this->bypassesAttendanceScope($user)) {
            return true;
        }

        return $this->accessibleClassSchedules($user, $sectionId, $date)
            ->contains('id', $classScheduleId);
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeClassAttendance(User $user, int $classScheduleId, int $sectionId, string $date): void
    {
        if (! $this->canAccessClassSchedule($user, $classScheduleId, $sectionId, $date)) {
            throw new AuthorizationException('You are not assigned to this class schedule.');
        }
    }

    /**
     * @return list<int>
     */
    public function accessibleStudentIds(User $user, int $sectionId, int $classScheduleId): array
    {
        if (! $this->canAccessClassSchedule($user, $classScheduleId, $sectionId, now()->toDateString())) {
            return [];
        }

        return $this->scheduleResolver
            ->studentsForSchedule($sectionId, $classScheduleId)
            ->pluck('id')
            ->all();
    }

    public function scopeSections(Builder $query, User $user): Builder
    {
        if ($this->bypassesScope($user)) {
            return $query;
        }

        $sectionIds = $this->accessibleSectionIds($user);

        return $query->whereIn('id', $sectionIds ?: [-1]);
    }

    public function scopeAttendanceSections(Builder $query, User $user): Builder
    {
        if ($this->bypassesAttendanceScope($user)) {
            return $query;
        }

        $sectionIds = $this->accessibleScheduleSectionIds($user);

        return $query->whereIn('id', $sectionIds ?: [-1]);
    }

    protected function hasAttendanceBypassRole(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::superAdminValue(),
            UserRole::Administrator->value,
            UserRole::Principal->value,
            UserRole::Registrar->value,
        ]);
    }

    public function studentsQuery(User $teacher): Builder
    {
        $sectionIds = $this->accessibleSectionIds($teacher);

        return Student::query()
            ->with(['gradeLevel.department', 'section'])
            ->whereIn('section_id', $sectionIds ?: [-1]);
    }
}
