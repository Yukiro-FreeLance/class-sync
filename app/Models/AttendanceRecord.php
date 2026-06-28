<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'method',
        'remarks',
        'latitude',
        'longitude',
        'device_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AttendanceStatus::class,
            'method' => AttendanceMethod::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->where('status', AttendanceStatus::Present);
    }

    public function scopeLate(Builder $query): Builder
    {
        return $query->where('status', AttendanceStatus::Late);
    }

    public function scopeAbsent(Builder $query): Builder
    {
        return $query->where('status', AttendanceStatus::Absent);
    }
}
