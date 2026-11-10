<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Assignment extends Model
{
    protected $table = 'equipment_assignments';

    protected $fillable = [
        'equipment_type',
        'equipment_id',
        'employee_id',
        'assigned_at',
        'returned_at',
        'remarks',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'returned_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function equipment(): MorphTo
    {
        return $this->morphTo(
            'equipment',
            'equipment_type',
            'equipment_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }
}
