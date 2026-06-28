<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Users\SuperAdminService;

class ApplicationPackagePolicy
{
    public function __construct(
        protected SuperAdminService $superAdmin,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->superAdmin->is($user);
    }

    public function create(User $user): bool
    {
        return $this->superAdmin->is($user);
    }

    public function delete(User $user): bool
    {
        return $this->superAdmin->is($user);
    }
}
