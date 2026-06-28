<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Visitor extends Model
{
    protected $fillable = [
        'name',
        'purpose',
        'time_in',
        'time_out',
        'contact_person',
        'id_number',
    ];

    protected function casts(): array
    {
        return [
            'time_in' => 'datetime',
            'time_out' => 'datetime',
        ];
    }

    public function scopeOnDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('time_in', $date);
    }

    public function scopeCurrentlyInside(Builder $query): Builder
    {
        return $query->whereNull('time_out');
    }
}
