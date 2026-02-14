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
    // 公開API（Step1）
    // ==========

    /**
     * 実働分（Step1）
     * - 早出は所定開始に丸め（所定開始より前の実績は無視）
     * - 休憩は勤務区間と被った分だけ控除
     * - 丸めは WorkRule.rounding_unit_minutes で切り捨て
     */
    public function workedMinutesForRule(WorkRule $rule): ?int
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        if (!$rule->work_start || !$rule->work_end) return null;

        $unit = $this->roundingUnitForRule($rule);

        // 実績区間（unitで切り捨て）
        [$actualStart, $actualEnd] = $this->actualPeriodRounded($unit);
        if ($actualEnd->lte($actualStart)) return 0;

        $date = $this->work_date->toDateString();

        // 所定区間
        [$schedStart, $schedEnd] = $this->periodFromTimeRange($date, $rule->work_start, $rule->work_end);

        // Step1: 早出は所定開始に丸める（開始は max(実績開始, 所定開始)）
        $start = $actualStart->lt($schedStart) ? $schedStart : $actualStart;

        // Step1: 勤務は所定終了を超えた実績も「実働」には含める（※残業は別関数で扱う）
        // もし「実働=所定内だけ」にしたいなら、ここを min($actualEnd, $schedEnd) にする
        $end = $actualEnd;

        if ($end->lte($start)) return 0;

        $worked = $start->diffInMinutes($end);

        // 休憩控除（勤務区間と被った場合のみ）
        if ($rule->break_start && $rule->break_end) {
            [$breakStart, $breakEnd] = $this->periodFromTimeRange($date, $rule->break_start, $rule->break_end);
            $worked -= $this->overlapMinutes($start, $end, $breakStart, $breakEnd);
        }

        return max(0, $worked);
    }

    /**
     * 所定勤務分（= ルールが決める勤務時間）
     * - 所定区間から休憩区間の被り分を控除
     */
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

    /**
     * 残業分（Step1）
     * - 残業は所定終了以降のみ
     * - 休憩と被った分は控除（ルール上の休憩帯が残業区間と被る場合）
     * - 丸めは WorkRule.rounding_unit_minutes で切り捨て
     */
    public function overtimeMinutesForRule(WorkRule $rule): ?int
    {
        if (!$this->clock_out) return null;
        if (!$rule->work_end) return null;

        $unit = $this->roundingUnitForRule($rule);

        $date = $this->work_date->toDateString();

        // 所定終了（残業開始点）
        $schedEnd = $this->dt($date, $rule->work_end);

        // 実績退勤（unitで切り捨てしてから計算すると “端数の扱い” が一貫する）
        $actualOut = $this->floorToUnit($this->clock_out->copy(), $unit);

        if ($actualOut->lte($schedEnd)) return 0;

        $oStart = $schedEnd;
        $oEnd   = $actualOut;

        // 休憩控除（残業区間と被った分だけ）
        $breakOverlap = 0;
        if ($rule->break_start && $rule->break_end) {
            [$breakStart, $breakEnd] = $this->periodFromTimeRange($date, $rule->break_start, $rule->break_end);
            $breakOverlap = $this->overlapMinutes($oStart, $oEnd, $breakStart, $breakEnd);
        }

        $raw = max(0, $oStart->diffInMinutes($oEnd) - $breakOverlap);

        // 端数切り捨て
        return intdiv($raw, $unit) * $unit;
    }

    /**
     * 深夜分（Step2以降想定）
     * ※丸めは WorkRule の unit を使ってもよいが、ここでは「実績丸め(unit)」を使うため引数を追加している
     */
    public function nightMinutesForRule(WorkRule $rule): ?int
    {
        $unit = $this->roundingUnitForRule($rule);

        $period = $this->actualPeriodRounded($unit);
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

    private function roundingUnitForRule(WorkRule $rule): int
    {
        // デフォルトは 10 としているが、あなたの運用で 15 が基本なら 15 にしてOK
        return max(1, (int) ($rule->rounding_unit_minutes ?? 10));
    }

    private function floorToUnit(Carbon $dt, int $unit): Carbon
    {
        $unit = max(1, $unit);
        $m = (int) $dt->minute;
        $floored = intdiv($m, $unit) * $unit;
        return $dt->copy()->setTime($dt->hour, $floored, 0);
    }

    /**
     * 実績区間を作って unit で切り捨て
     * - 日跨ぎ許可（clock_out <= clock_in なら翌日）
     */
    private function actualPeriodRounded(int $unit): ?array
    {
        if (!$this->clock_in || !$this->clock_out) return null;

        $start = $this->clock_in->copy();
        $end   = $this->clock_out->copy();

        if ($end->lte($start)) {
            $end->addDay();
        }

        $start = $this->floorToUnit($start, $unit);
        $end   = $this->floorToUnit($end, $unit);

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
