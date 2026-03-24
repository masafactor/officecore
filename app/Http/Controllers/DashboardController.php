<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\WorkRule;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $workRule = method_exists($user, 'currentWorkRule')
            ? $user->currentWorkRule(now())
            : null;

        if (! $workRule) {
            $workRule = WorkRule::where('name', '通常勤務')->first();
        }

        $missingClockOutDates = Attendance::query()
            ->where('user_id', $user->id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->where('work_date', '<', $today)
            ->orderByDesc('work_date')
            ->limit(10)
            ->pluck('work_date')
            ->map(fn ($d) => $d->toDateString());

        $workedMinutes = ($attendance && $workRule)
            ? $attendance->totalMinutesForRule($workRule)
            : null;

        $isOvertimeNow = false;

        if ($attendance && $workRule && $attendance->clock_in && ! $attendance->clock_out) {
            $date = $attendance->work_date->toDateString();

            $schedStart = Carbon::parse("{$date} {$workRule->work_start}");
            $schedEnd = Carbon::parse("{$date} {$workRule->work_end}");

            if ($schedEnd->lte($schedStart)) {
                $schedEnd->addDay();
            }

            $isOvertimeNow = now()->gt($schedEnd);
        }

        $openAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->orderByDesc('work_date')
            ->first();

        return Inertia::render('Dashboard', [
            'today' => $today,
            'workedMinutes' => $workedMinutes,
            'missingClockOutDates' => $missingClockOutDates,
            'attendance' => $attendance ? [
                'id' => $attendance->id,
                'work_date' => optional($attendance->work_date)->toDateString(),
                'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
                'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
                'note' => $attendance->note,
                'overtime_now' => $isOvertimeNow,
            ] : null,
            'openAttendance' => $openAttendance ? [
                'id' => $openAttendance->id,
                'work_date' => optional($openAttendance->work_date)->toDateString(),
                'clock_in' => $openAttendance->clock_in ? Carbon::parse($openAttendance->clock_in)->format('H:i') : null,
                'clock_out' => null,
            ] : null,
        ]);
    }
}