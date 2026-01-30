<?php

namespace App\Models;

use Carbon\Carbon;
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
    $period = $this->normalizeShiftPeriod();
    if (!$period) return null;

    [$start, $end] = $period;

    $totalMinutes = $start->diffInMinutes($end);

    // 休憩が未設定ならそのまま
    if (!$rule->break_start || !$rule->break_end) {
        return $totalMinutes;
    }

    // 休憩区間（※v2では「出勤日の休憩」として扱う。日跨ぎ休憩は未対応）
    $date = $this->work_date->toDateString();
    $breakStart = Carbon::parse($date)->setTimeFromTimeString((string)$rule->break_start);
    $breakEnd   = Carbon::parse($date)->setTimeFromTimeString((string)$rule->break_end);

    // 休憩が逆転してるなら翌日扱い（ここだけ軽く日跨ぎ対応）
    if ($breakEnd->lte($breakStart)) {
        $breakEnd->addDay();
    }

    $breakMinutes = $this->overlapMinutes($start, $end, $breakStart, $breakEnd);

    return max(0, $totalMinutes - $breakMinutes);
}


public function scheduledMinutesForRule(WorkRule $rule): ?int
{
    // 所定（work_start〜work_end）の分数（休憩は「重なり分だけ」控除）
    if (!$rule->work_start || !$rule->work_end) return null;

    $date = $this->work_date->toDateString();

    $start = Carbon::parse("{$date} {$rule->work_start}");
    $end   = Carbon::parse("{$date} {$rule->work_end}");

    if ($end->lte($start)) return null; // v1: 日跨ぎ所定は未対応

    $total = $start->diffInMinutes($end);

    if (!$rule->break_start || !$rule->break_end) return $total;

    $breakStart = Carbon::parse("{$date} {$rule->break_start}");
    $breakEnd   = Carbon::parse("{$date} {$rule->break_end}");
    if ($breakEnd->lte($breakStart)) return $total;

    // 重なり分だけ引く
    $overlapStart = $start->greaterThan($breakStart) ? $start : $breakStart;
    $overlapEnd   = $end->lessThan($breakEnd) ? $end : $breakEnd;

    $breakMinutes = $overlapEnd->gt($overlapStart)
        ? $overlapStart->diffInMinutes($overlapEnd)
        : 0;

    return max(0, $total - $breakMinutes);
}

public function overtimeMinutesForRule(WorkRule $rule): ?int
{
    $worked = $this->workedMinutesForRule($rule);
    $scheduled = $this->scheduledMinutesForRule($rule);

    if ($worked === null || $scheduled === null) return null;

    return max(0, $worked - $scheduled);
}

public function nightMinutes(): ?int
{
    $period = $this->normalizeShiftPeriod();
    if (!$period) return null;

    [$start, $end] = $period;

    $date = $this->work_date->toDateString();

    // 深夜区間：当日22:00〜翌日05:00
    $nightStart = Carbon::parse($date)->setTimeFromTimeString('22:00');
    $nightEnd   = Carbon::parse($date)->addDay()->setTimeFromTimeString('05:00');

    return $this->overlapMinutes($start, $end, $nightStart, $nightEnd);
}


private function normalizeShiftPeriod(): ?array
{
    if (!$this->clock_in || !$this->clock_out) return null;

    $start = $this->clock_in->copy();
    $end   = $this->clock_out->copy();

    // 日マタギ：退勤が出勤以前なら翌日に補正
    if ($end->lte($start)) {
        $end->addDay();
    }

    return [$start, $end];
}

private function overlapMinutes(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): int
{
    $start = $aStart->greaterThan($bStart) ? $aStart : $bStart;
    $end   = $aEnd->lessThan($bEnd) ? $aEnd : $bEnd;

    return $end->gt($start) ? $start->diffInMinutes($end) : 0;
}

}
