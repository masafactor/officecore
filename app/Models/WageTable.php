<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WageTable extends Model
{
    protected $fillable = [
        'employment_type_id',
        'code',
        'name',
        'hourly_wage',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function userEmployments(): HasMany
    {
        return $this->hasMany(UserEmployment::class);
    }
}