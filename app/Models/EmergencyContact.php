<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'relationship',
        'phone',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
