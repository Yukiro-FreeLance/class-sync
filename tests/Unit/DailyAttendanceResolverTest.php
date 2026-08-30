<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\Student;
use App\Services\Attendance\DailyAttendanceResolver;
use Database\Seeders\AttendanceRemarkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_worse_status_wins_when_gate_and_class_conflict(): void
    {
        $this->seed(AttendanceRemarkSeeder::class);

        $student = Student::factory()->create();
        $absentId = AttendanceRemark::query()->where('code', 'absent')->value('id');

        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatus::Late->value,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'attendance_remark_id' => $absentId,
            'date' => now()->toDateString(),
        ]);

        $status = app(DailyAttendanceResolver::class)
            ->resolveForStudents(now(), collect([$student->id]))
            ->get($student->id);

        $this->assertSame(AttendanceStatus::Absent, $status);
    }

    public function test_class_log_used_when_no_gate_record_exists(): void
    {
        $this->seed(AttendanceRemarkSeeder::class);

        $student = Student::factory()->create();
        $lateId = AttendanceRemark::query()->where('code', 'late')->value('id');

        AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'attendance_remark_id' => $lateId,
            'date' => now()->toDateString(),
        ]);

        $status = app(DailyAttendanceResolver::class)
            ->resolveForStudents(now(), collect([$student->id]))
            ->get($student->id);

        $this->assertSame(AttendanceStatus::Late, $status);
    }

    public function test_absent_beats_excused_across_class_periods(): void
    {
        $this->seed(AttendanceRemarkSeeder::class);

        $student = Student::factory()->create();
        $absentId = AttendanceRemark::query()->where('code', 'absent')->value('id');
        $excusedId = AttendanceRemark::query()->where('code', 'excused')->value('id');

        AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'attendance_remark_id' => $absentId,
            'date' => now()->toDateString(),
        ]);

        AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'attendance_remark_id' => $excusedId,
            'date' => now()->toDateString(),
        ]);

        $status = app(DailyAttendanceResolver::class)
            ->resolveForStudents(now(), collect([$student->id]))
            ->get($student->id);

        $this->assertSame(AttendanceStatus::Absent, $status);
    }

    public function test_gate_only_status_is_used_when_no_class_logs(): void
    {
        $student = Student::factory()->create();

        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatus::Excused->value,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $status = app(DailyAttendanceResolver::class)
            ->resolveForStudents(now(), collect([$student->id]))
            ->get($student->id);

        $this->assertSame(AttendanceStatus::Excused, $status);
    }
}
