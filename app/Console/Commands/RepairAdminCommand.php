<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\Users\SuperAdminService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RepairAdminCommand extends Command
{
    protected $signature = 'classsync:repair-admin
                            {--username=admin : Administrator username}
                            {--email=admin@classsync.local : Administrator email}
                            {--password=password : Administrator password}
                            {--first-name=System : First name}
                            {--last-name=Administrator : Last name}';

    protected $description = 'Create or repair the administrator account after a failed setup';

    public function handle(): int
    {
        $username = Str::lower(trim((string) $this->option('username')));
        $email = Str::lower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');

        $attributes = [
            'name' => trim($this->option('first-name').' '.$this->option('last-name')),
            'first_name' => $this->option('first-name'),
            'last_name' => $this->option('last-name'),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ];

        $user = User::query()
            ->where('email', $email)
            ->orWhere('username', $username)
            ->first();

        if (! $user) {
            $user = User::query()
                ->where(function ($query) {
                    $query->whereNull('email')
                        ->orWhere('email', '')
                        ->orWhereNull('username')
                        ->orWhere('username', '');
                })
                ->first();
        }

        if ($user) {
            $user->update($attributes);
            $this->info("Updated administrator account #{$user->id}.");
        } else {
            $user = User::query()->create($attributes);
            $this->info("Created administrator account #{$user->id}.");
        }

        $role = Role::firstOrCreate(
            [
                'name' => app(SuperAdminService::class)->setupRoleName(),
                'guard_name' => 'web',
            ],
            [
                'is_enabled' => true,
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(GradeLevelSeeder::class);
        $this->call(SectionSeeder::class);
        $this->call(SubjectSeeder::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->line("Username: {$username}");
        $this->line("Email: {$email}");
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }
}
