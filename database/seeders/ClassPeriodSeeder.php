<?php

namespace Database\Seeders;

use App\Models\ClassPeriod;
use Illuminate\Database\Seeder;

class ClassPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            ['name' => 'Period 1', 'code' => 'p1', 'starts_at' => '07:30:00', 'ends_at' => '08:30:00', 'sort_order' => 1],
            ['name' => 'Period 2', 'code' => 'p2', 'starts_at' => '08:30:00', 'ends_at' => '09:30:00', 'sort_order' => 2],
            ['name' => 'Period 3', 'code' => 'p3', 'starts_at' => '09:45:00', 'ends_at' => '10:45:00', 'sort_order' => 3],
            ['name' => 'Period 4', 'code' => 'p4', 'starts_at' => '10:45:00', 'ends_at' => '11:45:00', 'sort_order' => 4],
            ['name' => 'Period 5', 'code' => 'p5', 'starts_at' => '13:00:00', 'ends_at' => '14:00:00', 'sort_order' => 5],
            ['name' => 'Period 6', 'code' => 'p6', 'starts_at' => '14:00:00', 'ends_at' => '15:00:00', 'sort_order' => 6],
            ['name' => 'Period 7', 'code' => 'p7', 'starts_at' => '15:00:00', 'ends_at' => '16:00:00', 'sort_order' => 7],
            ['name' => 'Period 8', 'code' => 'p8', 'starts_at' => '16:00:00', 'ends_at' => '17:00:00', 'sort_order' => 8],
        ];

        foreach ($periods as $period) {
            ClassPeriod::query()->updateOrCreate(
                ['code' => $period['code']],
                array_merge($period, ['is_active' => true]),
            );
        }
    }
}
