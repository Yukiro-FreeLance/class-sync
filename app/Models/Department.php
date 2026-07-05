<?php

namespace App\Models;

use App\Enums\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
        'semesters',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'semesters' => 'array',
        ];
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function semesterEntries(): array
    {
        $semesters = $this->semesters;

        if (! is_array($semesters) || $semesters === []) {
            return collect(Semester::defaultValues())
                ->map(fn (string $code) => [
                    'code' => $code,
                    'label' => Semester::from($code)->label(),
                ])
                ->values()
                ->all();
        }

        if (isset($semesters[0]) && is_string($semesters[0])) {
            return collect($semesters)
                ->filter(fn (string $code) => Semester::tryFrom($code) !== null)
                ->map(fn (string $code) => [
                    'code' => $code,
                    'label' => Semester::from($code)->label(),
                ])
                ->values()
                ->all();
        }

        $entries = [];

        foreach ($semesters as $entry) {
            if (! is_array($entry) || empty($entry['code'])) {
                continue;
            }

            $code = (string) $entry['code'];

            if (Semester::tryFrom($code) === null) {
                continue;
            }

            $entries[] = [
                'code' => $code,
                'label' => trim((string) ($entry['label'] ?? '')) ?: Semester::from($code)->label(),
            ];
        }

        return $entries === []
            ? collect(Semester::defaultValues())
                ->map(fn (string $code) => [
                    'code' => $code,
                    'label' => Semester::from($code)->label(),
                ])
                ->values()
                ->all()
            : $entries;
    }

    /**
     * @return list<string>
     */
    public function configuredSemesters(): array
    {
        return array_column($this->semesterEntries(), 'code');
    }

    /**
     * @return array<string, string>
     */
    public function semesterOptions(): array
    {
        return collect($this->semesterEntries())
            ->mapWithKeys(fn (array $entry) => [$entry['code'] => $entry['label']])
            ->all();
    }

    public function labelForSemester(Semester|string $semester): string
    {
        $value = $semester instanceof Semester ? $semester->value : $semester;

        return $this->semesterOptions()[$value]
            ?? Semester::tryFrom($value)?->label()
            ?? ucfirst(str_replace('_', ' ', $value));
    }

    public function allowsSemester(Semester|string $semester): bool
    {
        $value = $semester instanceof Semester ? $semester->value : $semester;

        return in_array($value, $this->configuredSemesters(), true);
    }

    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class)->orderBy('sort_order');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isSeniorHigh(): bool
    {
        return $this->code === 'shs';
    }
}
