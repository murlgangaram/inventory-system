<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Printer extends Model
{
    protected $fillable = [
        'printer_name',
        'brand',
        'model',
        'serial_number',
        'office',
        'status',
        'remarks',
    ];

    // Assignment history for this printer
    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'equipment');
    }

    // Current active assignment
    public function activeAssignments(): MorphMany
    {
        return $this->assignments()
            ->whereNull('returned_at');
    }
}
