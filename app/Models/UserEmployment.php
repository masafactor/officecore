<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEmployment extends Model
{
    protected $fillable = [
        'user_id',
        'employment_type_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function isActiveOn(string|\Carbon\Carbon $date): bool
    {
        $d = \Carbon\Carbon::parse($date)->startOfDay();

        if ($this->start_date->gt($d)) return false;
        if ($this->end_date && $this->end_date->lt($d)) return false;

        return true;
    }
}
