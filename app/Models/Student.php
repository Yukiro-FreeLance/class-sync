<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Services\Students\StudentListService;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_number',
        'rfid_tag',
        'qr_code',
        'photo',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'birth_date',
        'address',
        'grade_level_id',
        'section_id',
        'course_id',
        'academic_year_id',
        'status',
        'medical_notes',
        'enrollment_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrollment_date' => 'date',
            'status' => StudentStatus::class,
        ];
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function currentEnrollment(): ?StudentEnrollment
    {
        $yearId = $this->academic_year_id;

        if (! $yearId) {
            return null;
        }

        return $this->enrollments()
            ->where('academic_year_id', $yearId)
            ->with(['gradeLevel', 'section', 'course', 'classSchedules.subject', 'classSchedules.teacher'])
            ->first();
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendancePeriodLogs(): HasMany
    {
        return $this->hasMany(AttendancePeriodLog::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function behaviorRecords(): HasMany
    {
        return $this->hasMany(BehaviorRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");

        if ($this->middle_name) {
            $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
        }

        return $name;
    }

    public function getListNameAttribute(): string
    {
        return StudentListService::formatName($this, 'lastname_first');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', StudentStatus::Active);
    }

    public function scopeInGradeLevel(Builder $query, int $gradeLevelId): Builder
    {
        return $query->where('grade_level_id', $gradeLevelId);
    }

    public function scopeInSection(Builder $query, int $sectionId): Builder
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForAcademicYear(Builder $query, int $academicYearId): Builder
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->newQuery()
            ->withTrashed()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
