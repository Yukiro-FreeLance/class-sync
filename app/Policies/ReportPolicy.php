<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    public function view(User $user): bool
    {
        return $user->can('reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    public function export(User $user): bool
    {
        return $user->can('reports.export');
    }
}
