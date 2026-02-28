<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportController extends Controller
{
    /**
     * 月次レポート（画面）
     */
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m')); // "2026-02"
        $rows = $this->buildRows($month);

        return Inertia::render('Admin/Reports/Monthly', [
            'filters' => [
                'month' => $month,
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * 月次レポート（CSV）
     */
    public function csv(Request $request): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $rows = $this->buildRows($month);

        $filename = "monthly_report_{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Excel対策：UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー（必要に応じて増減してOK）
            fputcsv($out, [
                '氏名',
                'メール',
                '出勤日数',
                '退勤日数',
                '実働日数',
                '合計実働(分)',
                '合計残業(分)',
                '合計深夜(分)',
                '遅刻(回)',
                '早退(回)',
                '平均実働(分)',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['user']['name'],
                    $r['user']['email'],
                    $r['clock_in_days'],
                    $r['clock_out_days'],
                    $r['worked_days'],
                    $r['worked_minutes_sum'],
                    $r['overtime_minutes_sum'],
                    $r['night_minutes_sum'],
                    $r['late_count'],
                    $r['early_leave_count'],
                    $r['worked_minutes_avg'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 月次集計データを作る
     *
     * 前提（あなたの今の設計に合わせてる）：
     * - User::currentWorkRule($date) がある
     * - User::employmentOn($date) がある（なければ null でOK）
     * - Attendance::workedMinutesForRule(WorkRule $rule) がある
     * - Attendance::nightMinutesForRule(WorkRule $rule) がある（なければ 0 で扱う）
     * - Attendance::overtimeMinutesWithPolicy(WorkRule $rule, string $policy) を後で作る想定
     *   （未実装なら overtimeMinutesForRule($rule) にフォールバックする）
     */
    private function buildRows(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // fallback（ユーザーに履歴が無い時）
        $fallbackRule = WorkRule::where('name', '通常勤務')->first();

        $items = Attendance::query()
            ->with([
                'user:id,name,email',
                // currentWorkRule / employmentOn が「$this->userWorkRules」みたいに
                // リレーションをコレクション参照してるので eager load しとく（N+1防止）
                'user.userWorkRules.workRule',
                'user.userEmployments.employmentType',
            ])
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('user_id')
            ->orderBy('work_date')
            ->get()
            ->groupBy('user_id');

        return $items->map(function ($rows) use ($fallbackRule) {
            $user = $rows->first()->user;

            $workedMinutesSum   = 0;
            $overtimeMinutesSum = 0;
            $nightMinutesSum    = 0;

            $workedDays   = 0;
            $clockInDays  = 0;
            $clockOutDays = 0;

            $lateCount      = 0;
            $earlyLeaveCount = 0;

            foreach ($rows as $a) {
                if ($a->clock_in)  $clockInDays++;
                if ($a->clock_out) $clockOutDays++;

                // すでに出してるフラグ（DBカラムの想定）
                if (!empty($a->is_late))        $lateCount++;
                if (!empty($a->is_early_leave)) $earlyLeaveCount++;

                // その日のルール（無ければ通常勤務）
                $rule = null;
                if ($user && method_exists($user, 'currentWorkRule')) {
                    $rule = $user->currentWorkRule($a->work_date);
                }
                $rule = $rule ?? $fallbackRule;

                if (!$rule) continue;

                // 実働
                $m = $a->workedMinutesForRule($rule);
                if ($m !== null) {
                    $workedMinutesSum += (int) $m;
                    $workedDays++;
                }

                // 深夜（あるなら）
                if (method_exists($a, 'nightMinutesForRule')) {
                    $nm = $a->nightMinutesForRule($rule);
                    if ($nm !== null) $nightMinutesSum += (int) $nm;
                }

                // 残業：employment policy があるならそれ、なければ今の overtimeMinutesForRule
                $policy = 'scheduled_over';
                if ($user && method_exists($user, 'employmentOn')) {
                    $employmentType = $user->employmentOn($a->work_date);
                    // employment_types に overtime_policy を持たせる想定
                    if ($employmentType && property_exists($employmentType, 'overtime_policy')) {
                        $policy = $employmentType->overtime_policy ?? $policy;
                    } elseif ($employmentType && isset($employmentType->overtime_policy)) {
                        $policy = $employmentType->overtime_policy ?? $policy;
                    }
                }

                if (method_exists($a, 'overtimeMinutesWithPolicy')) {
                    $om = $a->overtimeMinutesWithPolicy($rule, $policy);
                } else {
                    // まだメソッド作ってないならここで暫定
                    $om = $a->overtimeMinutesForRule($rule);
                }
                if ($om !== null) $overtimeMinutesSum += (int) $om;
            }

            $avg = $workedDays > 0 ? intdiv($workedMinutesSum, $workedDays) : null;

            return [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'clock_in_days'        => $clockInDays,
                'clock_out_days'       => $clockOutDays,
                'worked_days'          => $workedDays,
                'worked_minutes_sum'   => $workedMinutesSum,
                'overtime_minutes_sum' => $overtimeMinutesSum,
                'night_minutes_sum'    => $nightMinutesSum,
                'late_count'           => $lateCount,
                'early_leave_count'    => $earlyLeaveCount,
                'worked_minutes_avg'   => $avg,
            ];
        })->values()->all();
    }
}