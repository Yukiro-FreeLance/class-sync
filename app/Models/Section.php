<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected $fillable = [
        'grade_level_id',
        'academic_year_id',
        'name',
        'adviser_id',
        'room',
        'room_id',
    ];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assignedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeForGradeLevel(Builder $query, int $gradeLevelId): Builder
    {
        return $query->where('grade_level_id', $gradeLevelId);
    }
}
