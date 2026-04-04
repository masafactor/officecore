<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCalendarDay extends Model
{
    protected $fillable = [
        'calendar_date',
        'day_type',
        'scheduled_minutes',
        'is_public_holiday',
        'holiday_name',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'calendar_date' => 'date',
            'scheduled_minutes' => 'integer',
            'is_public_holiday' => 'boolean',
        ];
    }
}