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
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m')); // "2026-01"
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rule = WorkRule::where('name', '通常勤務')->first();

        $rows = $this->buildRows($month);


        return Inertia::render('Admin/Reports/Monthly', [
            'filters' => [
                'month' => $month,
            ],
            'rows' => $rows,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        // index() と同じ集計を再利用したいので、まず rows を作る
        // ※ もし index() のロジックが長いなら後で private 関数に切り出すと綺麗
        $rows = $this->buildRows($month); // 後で追加する private メソッド

        $filename = "monthly_report_{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Excel対策：UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($out, ['氏名', 'メール', '出勤日数', '退勤日数', '実働日数', '合計実働(分)', '平均実働(分)']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['user']['name'],
                    $r['user']['email'],
                    $r['clock_in_days'],
                    $r['clock_out_days'],
                    $r['worked_days'],
                    $r['worked_minutes_sum'],
                    $r['worked_minutes_avg'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildRows(string $month): array
    {
        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rule = \App\Models\WorkRule::where('name', '通常勤務')->first();

        return \App\Models\Attendance::query()
            ->with(['user:id,name,email'])
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('user_id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) use ($rule) {
                $user = $items->first()->user;

                $workedMinutesSum = 0;
                $workedDays = 0;
                $clockInDays = 0;
                $clockOutDays = 0;

                foreach ($items as $a) {
                    if ($a->clock_in) $clockInDays++;
                    if ($a->clock_out) $clockOutDays++;

                    if ($rule) {
                        $m = $a->workedMinutesForRule($rule);
                        if ($m !== null) {
                            $workedMinutesSum += $m;
                            $workedDays++;
                        }
                    }
                }

                $avg = $workedDays > 0 ? intdiv($workedMinutesSum, $workedDays) : null;

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'clock_in_days' => $clockInDays,
                    'clock_out_days' => $clockOutDays,
                    'worked_days' => $workedDays,
                    'worked_minutes_sum' => $workedMinutesSum,
                    'worked_minutes_avg' => $avg,
                ];
            })
            ->values()
            ->all();
    }


}
