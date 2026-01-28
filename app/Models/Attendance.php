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

public function workedMinutesForRule(WorkRule $rule): ?int
{
    if (!$this->clock_in || !$this->clock_out) {
        return null;
    }

    $start = $this->clock_in->copy();
    $end   = $this->clock_out->copy();

    if ($end->lte($start)) {
        return 0;
    }

    $totalMinutes = $start->diffInMinutes($end);

    // 休憩が未設定ならそのまま
    if (!$rule->break_start || !$rule->break_end) {
        return $totalMinutes;
    }

    // 休憩時間を同日の日時にする（work_date基準）
    $date = $this->work_date->toDateString();

    $breakStart = $start->copy()->setDateFrom($this->work_date)->setTimeFromTimeString($rule->break_start);
    $breakEnd   = $start->copy()->setDateFrom($this->work_date)->setTimeFromTimeString($rule->break_end);

    // 休憩が逆転してたら無視（安全策）
    if ($breakEnd->lte($breakStart)) {
        return $totalMinutes;
    }

    // 重なり分だけ引く
    $overlapStart = $start->greaterThan($breakStart) ? $start : $breakStart;
    $overlapEnd   = $end->lessThan($breakEnd) ? $end : $breakEnd;

    $breakMinutes = $overlapEnd->gt($overlapStart)
        ? $overlapStart->diffInMinutes($overlapEnd)
        : 0;

    return max(0, $totalMinutes - $breakMinutes);
}

}
