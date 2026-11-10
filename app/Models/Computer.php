<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Computer extends Model
{
    protected $fillable = [
        'computer_name',
        'office',
        'ram',
        'cpu',
        'ssd',
        'hd',
        'brand',
        'model',
        'serial_number',
        'mac_address',
        'status',
        'remarks',
    ];

    // Assignment history for this computer
    public function assignments(): MorphMany
    {
        return $this->morphMany(
            EquipmentAssignment::class,
            'equipment'
        );
    }

    // Current assignment history
    public function activeAssignments(): MorphMany
    {
        return $this->assignments()
            ->whereNull('returned_at');
    }
}
