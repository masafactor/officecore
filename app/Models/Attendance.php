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
    //
    // 方針（Step1）
    // - 実績の「時刻」は丸めない（clock_in / clock_out は生のまま扱う）
    // - 早出は所定開始に合わせる（所定開始より前は無視）
    // - 休憩は勤務区間と被った分だけ控除
    // - 丸めは「合計分」に対して WorkRule.rounding_unit_minutes で切り捨て（最後にだけ）
    //

    /**
     * 実働分（Step1）
     * - 早出は所定開始に丸め（所定開始より前の実績は無視）
     * - 休憩は勤務区間と被った分だけ控除
     * - 丸めは「合計分」で切り捨て（WorkRule.rounding_unit_minutes）
     *
     * NOTE:
     * - 実働を「所定内だけ」にする（残業と二重計上しない）ため、終了は min(実績退勤, 所定終了) にしている。
     *   もし「実働=所定外も含めた全勤務」にしたいなら、$end を $actualEnd に変更し、表示側の意味付けを調整すること。
     */
    public function workedMinutesForRule(WorkRule $rule): ?int
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        if (!$rule->work_start || !$rule->work_end) return null;

        $unit = $this->roundingUnitForRule($rule);

        // 実績区間（丸めなし / 日跨ぎ補正あり）
        [$actualStart, $actualEnd] = $this->actualPeriodRaw();
        if ($actualEnd->lte($actualStart)) return 0;

        $date = $this->work_date->toDateString();

        // 所定区間
        [$schedStart, $schedEnd] = $this->periodFromTimeRange($date, $rule->work_start, $rule->work_end);

        // 早出カット：開始は max(実績開始, 所定開始)
        $start = $actualStart->lt($schedStart) ? $schedStart : $actualStart;

        // 実働（所定内）：終了は min(実績退勤, 所定終了)
        $end = $actualEnd->gt($schedEnd) ? $schedEnd : $actualEnd;

        if ($end->lte($start)) return 0;

        $worked = $start->diffInMinutes($end);

        // 休憩控除（勤務区間と被った分だけ）
        if ($rule->break_start && $rule->break_end) {
            [$breakStart, $breakEnd] = $this->periodFromTimeRange($date, $rule->break_start, $rule->break_end);
            $worked -= $this->overlapMinutes($start, $end, $breakStart, $breakEnd);
        }

        $worked = max(0, $worked);

        // 合計分で切り捨て（ここだけ丸め）
        return $this->floorMinutes($worked, $unit);
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
     * - 丸めは「合計分」で切り捨て（WorkRule.rounding_unit_minutes）
     */
    public function overtimeMinutesForRule(WorkRule $rule): ?int
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        if (!$rule->work_end) return null;

        $unit = $this->roundingUnitForRule($rule);
        $date = $this->work_date->toDateString();

        // 所定終了（残業開始点）
        $schedEnd = $this->dt($date, $rule->work_end);

        // 実績退勤（丸めなし / 日跨ぎ補正あり）
        [, $actualOut] = $this->actualPeriodRaw();

        // 所定終了以前は残業なし
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

        // 合計分で切り捨て
        return $this->floorMinutes($raw, $unit);
    }

    /**
     * 深夜分（Step2以降想定）
     * - 実績の「時刻」は丸めずに重なり分を計算
     * - 丸めを入れる場合は「合計分」で切り捨て（ここでは unit に揃える）
     */
    public function nightMinutesForRule(WorkRule $rule): ?int
    {
        if (!$this->clock_in || !$this->clock_out) return null;

        $unit = $this->roundingUnitForRule($rule);

        [$start, $end] = $this->actualPeriodRaw();

        $date = $this->work_date->toDateString();

        // 深夜：当日22:00〜翌日05:00
        $nightStart = $this->dt($date, '22:00');
        $nightEnd   = $this->dt($date, '05:00')->addDay();

        $raw = $this->overlapMinutes($start, $end, $nightStart, $nightEnd);

        return $this->floorMinutes($raw, $unit);
    }

    // ==========
    // 内部ヘルパ
    // ==========

    private function roundingUnitForRule(WorkRule $rule): int
    {
        // デフォルトは 10（運用が 15 基本なら 15 に変更してOK）
        return max(1, (int) ($rule->rounding_unit_minutes ?? 10));
    }

    private function floorMinutes(int $minutes, int $unit): int
    {
        $unit = max(1, $unit);
        $minutes = max(0, $minutes);
        return intdiv($minutes, $unit) * $unit;
    }

    /**
     * 実績区間を作る（丸めなし）
     * - 日跨ぎ許可（clock_out <= clock_in なら翌日）
     */
    private function actualPeriodRaw(): array
    {
        $start = $this->clock_in->copy();
        $end   = $this->clock_out->copy();

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

    /**
     * 総労働時間（表示用）
     * = 所定内実働 + 残業
     */
    public function totalMinutesForRule(WorkRule $rule): ?int
    {
        $w = $this->workedMinutesForRule($rule);
        $o = $this->overtimeMinutesForRule($rule);

        if ($w === null && $o === null) return null;
        return (int)($w ?? 0) + (int)($o ?? 0);
    }

    public function updateLateEarlyFlags(WorkRule $rule): void
    {
        if (!$this->clock_in || !$this->clock_out) {
            return;
        }

        [$schedStart, $schedEnd] = $this->periodFromTimeRange(
            $this->work_date->toDateString(),
            $rule->work_start,
            $rule->work_end
        );

        $this->is_late = $this->clock_in->gt($schedStart);
        $this->is_early_leave = $this->clock_out->lt($schedEnd);
    }

    public function overtimeMinutesWithPolicy(WorkRule $rule, string $policy): ?int
    {
        $total = $this->totalMinutesForRule($rule);

        if ($total === null) return null;

        if ($policy === 'legal_over') {
            return max(0, $total - 480); // 8時間
        }

        // scheduled_over
        return $this->overtimeMinutesForRule($rule);
    }
}
