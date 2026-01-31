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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========
    // 公開API
    // ==========

    public function workedMinutesForRule(WorkRule $rule): ?int
    {
        $period = $this->normalizeShiftPeriod();
        if (!$period) return null;
        [$start, $end] = $period;

        $total = $start->diffInMinutes($end);

        // 休憩が未設定ならそのまま
        if (!$rule->break_start || !$rule->break_end) {
            return $total;
        }

        [$breakStart, $breakEnd] = $this->periodFromTimeRange(
            $this->work_date->toDateString(),
            $rule->break_start,
            $rule->break_end
        );

        $breakMinutes = $this->overlapMinutes($start, $end, $breakStart, $breakEnd);

        return max(0, $total - $breakMinutes);
    }

    public function scheduledMinutesForRule(WorkRule $rule): ?int
    {
        if (!$rule->work_start || !$rule->work_end) return null;

        [$start, $end] = $this->periodFromTimeRange(
            $this->work_date->toDateString(),
            $rule->work_start,
            $rule->work_end
        );

        $total = $start->diffInMinutes($end);

        if (!$rule->break_start || !$rule->break_end) return $total;

        [$breakStart, $breakEnd] = $this->periodFromTimeRange(
            $this->work_date->toDateString(),
            $rule->break_start,
            $rule->break_end
        );

        $breakMinutes = $this->overlapMinutes($start, $end, $breakStart, $breakEnd);

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

        // 深夜：当日22:00〜翌日05:00
        $nightStart = $this->dt($date, '22:00');
        $nightEnd   = $this->dt($date, '05:00')->addDay();

        return $this->overlapMinutes($start, $end, $nightStart, $nightEnd);
    }

    // ==========
    // 内部ヘルパ
    // ==========

    private function normalizeShiftPeriod(): ?array
    {
        if (!$this->clock_in || !$this->clock_out) return null;

        $start = $this->clock_in->copy();
        $end   = $this->clock_out->copy();

        // ✅ 日マタギ：退勤が出勤以前なら翌日に補正
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * "日付 + (時刻文字列)" から Carbon を作る
     * - $time は "09:00" or "09:00:00" を想定
     */
    private function dt(string $date, string $time): Carbon
    {
        $time = $this->normalizeTimeString($time); // "H:i" -> "H:i:s"
        $tz = config('app.timezone', 'Asia/Tokyo');

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}", $tz);
    }

    /**
     * (start,end) の期間を日付ベースで作る。end<=startなら end を翌日にする（跨ぎ許可）
     */
    private function periodFromTimeRange(string $date, string $startTime, string $endTime): array
    {
        $start = $this->dt($date, $startTime);
        $end   = $this->dt($date, $endTime);

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

    private function normalizeTimeString(string $time): string
    {
        // "HH:MM" を "HH:MM:00" に統一
        return preg_match('/^\d{2}:\d{2}$/', $time) ? "{$time}:00" : $time;
    }
}

