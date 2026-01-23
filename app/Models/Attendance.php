<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workedMinutesForRule(\App\Models\WorkRule $rule): ?int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        $total = $this->clock_out->diffInMinutes($this->clock_in);

        $breakMinutes = \Carbon\Carbon::parse($rule->break_end)
            ->diffInMinutes(\Carbon\Carbon::parse($rule->break_start));

        $worked = $total - $breakMinutes;
        return max(0, $worked);
    }

}
