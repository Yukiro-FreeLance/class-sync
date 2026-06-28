<?php

namespace Database\Seeders;

use App\Models\AttendanceRemark;
use Illuminate\Database\Seeder;

class AttendanceRemarkSeeder extends Seeder
{
    public function run(): void
    {
        $remarks = [
            ['code' => 'present', 'label' => 'Present', 'color' => '#10b981', 'counts_as_present' => true, 'is_default' => true, 'sort_order' => 1],
            ['code' => 'late', 'label' => 'Late', 'color' => '#f59e0b', 'counts_as_present' => true, 'is_default' => false, 'sort_order' => 2],
            ['code' => 'absent', 'label' => 'Absent', 'color' => '#ef4444', 'counts_as_present' => false, 'is_default' => false, 'sort_order' => 3],
            ['code' => 'excused', 'label' => 'Excused', 'color' => '#6366f1', 'counts_as_present' => false, 'is_default' => false, 'sort_order' => 4],
            ['code' => 'half_day', 'label' => 'Half Day', 'color' => '#8b5cf6', 'counts_as_present' => false, 'is_default' => false, 'sort_order' => 5],
        ];

        foreach ($remarks as $remark) {
            AttendanceRemark::query()->updateOrCreate(
                ['code' => $remark['code']],
                array_merge($remark, ['is_active' => true]),
            );
        }
    }
}
