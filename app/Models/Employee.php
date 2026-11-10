<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id_number',
        'full_name',
        'position',
        'job_description',
        'office',
    ];

    // All equipment assignment records for this employee
    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    // Equipment currently assigned to this employee
    public function activeAssignments(): HasMany
    {
        return $this->assignments()
            ->whereNull('returned_at');
    }
}
