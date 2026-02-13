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
        'rounding_unit_minutes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
        'rounding_unit_minutes' => 'integer',
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========
    // 公開API
    // ==========



    public function workedMinutesForRule(WorkRule $rule)
    {
        $period = $this->normalizeShiftPeriod();
        if (!$period) return null;
        [$start, $end] = $period;

        // 早出は所定開始に合わせる（所定があるときだけ）
        if ($rule->work_start) {
            [$schedStart, $_] = $this->periodFromTimeRange($this->work_date->toDateString(), $rule->work_start, $rule->work_start);
            // ↑ periodFromTimeRange は end<=start で翌日になるので、ここは dt() で作る方が安全
        }
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
        if (!$this->clock_out || !$rule->work_start || !$rule->work_end) return null;

        $date = $this->work_date->toDateString();

        // 所定の勤務時間帯
        [$schedStart, $schedEnd] = $this->periodFromTimeRange($date, $rule->work_start, $rule->work_end);

        $actualOut = $this->clock_out->copy();

        // 残業は所定終了より後だけ
        $raw = max(0, $schedEnd->diffInMinutes($actualOut, false));

        $unit = max(1, (int) ($rule->rounding_unit_minutes ?? 10));

        // 端数切り捨て（例: 19分 -> 10分、29分 -> 20分）
        return intdiv($raw, $unit) * $unit;
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

    private function floorToQuarter(Carbon $dt): Carbon
    {
        // 15分単位に切り捨て（例: 09:14 → 09:00、09:29 → 09:15）
        $m = (int) $dt->minute;
        $floored = intdiv($m, 15) * 15;

        return $dt->copy()->setTime($dt->hour, $floored, 0);
    }

    private function normalizeShiftPeriod(): ?array
    {
        if (!$this->clock_in || !$this->clock_out) return null;

        $start = $this->clock_in->copy();
        $end   = $this->clock_out->copy();

        // 日跨ぎ
        if ($end->lte($start)) {
            $end->addDay();
        }

        // ✅ 15分切り捨て（Step1仕様）
        $start = $this->floorToQuarter($start);
        $end   = $this->floorToQuarter($end);

        // 切り捨て後に逆転するケースは0扱い（安全策）
        if ($end->lte($start)) {
            return [$start, $start];
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

