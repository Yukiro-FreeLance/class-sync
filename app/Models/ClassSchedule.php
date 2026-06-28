<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $fillable = [
        'academic_year_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'room_id',
        'semester',
        'day_of_week',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'semester' => Semester::class,
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function periodLogs(): HasMany
    {
        return $this->hasMany(AttendancePeriodLog::class);
    }

    public function getTimeRangeAttribute(): string
    {
        return substr((string) $this->starts_at, 0, 5).'–'.substr((string) $this->ends_at, 0, 5);
    }

    public function getDisplayLabelAttribute(): string
    {
        $subject = $this->subject?->name ?? 'Class';
        $day = $this->day_of_week?->shortLabel() ?? '';

        return trim("{$subject} · {$day} {$this->time_range}");
    }
}
