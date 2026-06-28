<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriodLog extends Model
{
    protected $fillable = [
        'student_id',
        'class_period_id',
        'class_schedule_id',
        'section_id',
        'attendance_remark_id',
        'date',
        'remarks',
        'went_out_at',
        'returned_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function remark(): BelongsTo
    {
        return $this->belongsTo(AttendanceRemark::class, 'attendance_remark_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttendancePeriodEvent::class)->orderBy('recorded_at');
    }
}
