<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Users\SuperAdminService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $admin = User::query()->create([
            'name' => 'System Administrator',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@classsync.local',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $admin->assignRole(app(SuperAdminService::class)->setupRoleName());
    }
}
