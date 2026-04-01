<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCalendarDay extends Model
{
    protected $fillable = [
        'calendar_date',
        'day_type',
        'scheduled_minutes',
        'note',
    ];

    protected $casts = [
        'calendar_date' => 'date',
    ];
}