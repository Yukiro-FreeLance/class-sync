<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Services\Users\SuperAdminService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'username',
    'email',
    'password',
    'is_active',
    'acts_as_teacher',
    'last_login_at',
    'last_login_ip',
    'avatar',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'acts_as_teacher' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return (string) $this->name;
    }

    public function advisedSections(): HasMany
    {
        return $this->hasMany(Section::class, 'adviser_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function behaviorRecordsRecorded(): HasMany
    {
        return $this->hasMany(BehaviorRecord::class, 'recorded_by');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'uploaded_by');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class, 'created_by');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'teacher_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAssignableAsTeacher(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->role(UserRole::Teacher->value)
                ->orWhere('acts_as_teacher', true);
        });
    }

    public function canActAsTeacher(): bool
    {
        return $this->hasRole(UserRole::Teacher->value) || $this->acts_as_teacher;
    }

    public function isSuperAdmin(): bool
    {
        return app(SuperAdminService::class)->is($this);
    }

    public function hasUnrestrictedAccess(): bool
    {
        return $this->hasAnyRole(
            collect(UserRole::unrestricted())->map->value->all(),
        );
    }

    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return app(SuperAdminService::class)->applyUserVisibilityScope($query, $viewer);
    }

    public function canAssignSuperAdminRole(): bool
    {
        return app(SuperAdminService::class)->canAssignRole($this);
    }
}
