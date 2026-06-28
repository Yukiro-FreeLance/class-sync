<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SetupDatabaseSeeder extends Seeder
{
    /**
     * Minimal seeding for first-time setup wizard (roles/permissions only).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            GradeLevelSeeder::class,
            SectionSeeder::class,
            SubjectSeeder::class,
            AttendanceRemarkSeeder::class,
            ClassPeriodSeeder::class,
        ]);
    }
}
