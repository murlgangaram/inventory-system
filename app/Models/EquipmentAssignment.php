<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EquipmentAssignment extends Model
{
    protected $fillable = [
        'equipment_type',
        'equipment_id',
        'employee_id',
        'assigned_at',
        'returned_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    // Employee who received the equipment
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Computer or printer connected to this assignment
    public function equipment(): MorphTo
    {
        return $this->morphTo();
    }
}
