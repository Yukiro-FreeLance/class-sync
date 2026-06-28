<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationStatus extends Model
{
    protected $table = 'installation_status';

    protected $fillable = [
        'is_installed',
        'installed_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }
}
