<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceClosing;
use App\Models\WorkRule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        

        // 例: ?month=2026-02（未指定なら今月）
        $month = $request->string('month')->toString();
        if (!$month) {
            $month = now()->format('Y-m');
        }

        // month -> from/to（"YYYY-MM"前提）
        [$y, $m] = array_map('intval', explode('-', $month));
        $from = Carbon::create($y, $m, 1)->startOfMonth();
        $to   = (clone $from)->endOfMonth();


        $closing = AttendanceClosing::for($request->user(), $y, $m);

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date', 'desc')
            ->paginate(31)
            ->through(function (Attendance $a) use ($user) {

                $date = $a->work_date?->toDateString() ?? now()->toDateString();

                // 勤務ルール（その日付で有効なもの）
                $rule = $user->currentWorkRule(Carbon::parse($date));
                if (!$rule) {
                    $rule = WorkRule::where('name', '通常勤務')->first();
                }

                // 雇用（その日付で有効なもの）
                $employment = method_exists($user, 'employmentOn')
                    ? $user->employmentOn(Carbon::parse($date))
                    : null;

                $policy = $employment?->overtime_policy ?? 'scheduled_over';

                // 所定労働時間計算（退勤が無い日は null になりやすいので 0 へ寄せる）
                $scheduled_work_minutes = ($a->clock_in && $a->clock_out && $rule)
                    ? $a->workedWithinScheduleMinutesForRule($rule)
                    : null;

                // A案：法定内/法定外（モデルにメソッドがある想定）
                $breakdown = ($a->clock_in && $a->clock_out && $rule && method_exists($a, 'overtimeBreakdownMinutes'))
                    ? $a->overtimeBreakdownMinutes($rule, $policy) // ['in'=>..,'out'=>..] or null
                    : null;

                // 深夜
                $nightMinutes = ($a->clock_in && $a->clock_out && $rule)
                    ? $a->nightMinutesForRule($rule)
                    : null;

                $total_work_minutes = ($a->clock_in && $a->clock_out && $rule)
                ? $a->totalMinutesForRule($rule)
                : null;

                return [
                    'id' => $a->id,
                    'work_date' => $date,

                    // 表示用（HH:MM）
                    'clock_in'  => optional($a->clock_in)->format('H:i'),
                    'clock_out' => optional($a->clock_out)->format('H:i'),

                    'note' => $a->note,
                    'updated_at' => optional($a->updated_at)->toDateTimeString(),

                    'is_late' => (bool)($a->is_late ?? false),
                    'is_early_leave' => (bool)($a->is_early_leave ?? false),

                    // A案の返却（分）
                    'minutes' => [
                        // 実働は画面で必要なら使う
                        'workescheduled_work_minutesd' => $scheduled_work_minutes ?? 0,

                        'total_work_minutes' => $total_work_minutes ?? 0,

                        // 残業（法定内/法定外）
                        'overtime' => [
                            'in'  => $breakdown['in']  ?? 0,
                            'out' => $breakdown['out'] ?? 0,
                        ],

                        // 深夜（分）
                        'night' => $nightMinutes ?? 0,
                    ],

                    // デバッグしやすいように一応返しておく（不要なら消してOK）
                    'meta' => [
                        'work_rule' => $rule ? ['id' => $rule->id, 'name' => $rule->name] : null,
                        'overtime_policy' => $policy,
                    ],

                    
                ];
                
            });
            

        
        return Inertia::render('Attendances/Index', [
            'filters' => [
                'month' => $month,
            ],
            'attendances' => $attendances,

            'closing' => [
            'status' => $closing?->status ?? AttendanceClosing::STATUS_DRAFT,
            'submitted_at' => $closing?->submitted_at?->toISOString(),
            'approved_at' => $closing?->approved_at?->toISOString(),
            'approved_by' => $closing?->approved_by,
            ],
        ]);
    }
}
