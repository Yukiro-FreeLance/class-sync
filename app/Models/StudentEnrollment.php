<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'grade_level_id',
        'section_id',
        'course_id',
        'status',
        'enrollment_date',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'status' => EnrollmentStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classSchedules(): BelongsToMany
    {
        return $this->belongsToMany(ClassSchedule::class, 'student_enrollment_classes')
            ->withTimestamps();
    }
}
