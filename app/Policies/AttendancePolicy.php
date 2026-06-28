<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.create');
    }

    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->can('attendance.update');
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->can('attendance.delete');
    }
}
