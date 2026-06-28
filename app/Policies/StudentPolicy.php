<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('students.view');
    }

    public function create(User $user): bool
    {
        return $user->can('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        if ($student->trashed()) {
            return false;
        }

        return $user->can('students.update');
    }

    public function archive(User $user, Student $student): bool
    {
        if ($student->trashed()) {
            return false;
        }

        return $user->can('students.archive');
    }

    public function restore(User $user, Student $student): bool
    {
        if (! $student->trashed()) {
            return false;
        }

        return $user->can('students.restore');
    }

    public function delete(User $user, Student $student): bool
    {
        if (! $student->trashed()) {
            return false;
        }

        return $user->can('students.delete');
    }

    public function enroll(User $user, Student $student): bool
    {
        if ($student->trashed()) {
            return false;
        }

        return $user->can('students.update');
    }

    public function forceDelete(User $user, Student $student): bool
    {
        if (! $student->trashed()) {
            return false;
        }

        return $user->can('students.delete');
    }
}
