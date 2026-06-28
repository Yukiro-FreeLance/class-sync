<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePeriodEvent extends Model
{
    protected $fillable = [
        'attendance_period_log_id',
        'event_type',
        'remarks',
        'recorded_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function periodLog(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriodLog::class, 'attendance_period_log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
