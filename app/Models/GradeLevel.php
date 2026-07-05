<?php

namespace App\Models;

use Database\Factories\GradeLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    /** @use HasFactory<GradeLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function isSeniorHigh(): bool
    {
        return $this->department?->code === 'shs';
    }
}
