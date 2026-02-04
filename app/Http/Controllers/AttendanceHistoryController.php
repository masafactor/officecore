<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        // month -> from/to
        // "YYYY-MM" を前提
        [$y, $m] = array_map('intval', explode('-', $month));
        $from = now()->setDate($y, $m, 1)->startOfDay();
        $to   = (clone $from)->endOfMonth()->endOfDay();

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date', 'desc')
            ->paginate(31)
            ->through(fn ($a) => [
                'id' => $a->id,
                'work_date' => $a->work_date->toDateString(),
                'clock_in'  => optional($a->clock_in)->format('H:i'),
                'clock_out' => optional($a->clock_out)->format('H:i'),
                // 実働は仕様未確定なので一旦表示しない or null
                'note' => $a->note,
                'updated_at' => optional($a->updated_at)->toDateTimeString(),
            ]);

        return Inertia::render('Attendances/Index', [
            'filters' => [
                'month' => $month,
            ],
            'attendances' => $attendances,
        ]);
    }
}
