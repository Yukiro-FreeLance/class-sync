<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $startYear = (int) now()->format('Y');
        $endYear = $startYear + 1;

        AcademicYear::query()->updateOrCreate(
            ['name' => "{$startYear}-{$endYear}"],
            [
                'start_date' => "{$startYear}-06-01",
                'end_date' => "{$endYear}-03-31",
                'is_current' => true,
            ],
        );

        AcademicYear::query()
            ->where('name', '!=', "{$startYear}-{$endYear}")
            ->update(['is_current' => false]);
    }
}
