<?php

use App\Http\Controllers\ProfileController;
use App\Models\Attendance;
use App\Models\WorkRule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AttendanceCorrectionController as AdminAttendanceCorrectionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\DailyReportController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get('/dashboard', function () {
    $user = request()->user();
    $today = now()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
        ->where('work_date', $today)
        ->first();

    $workRule = WorkRule::query()
        ->whereHas('userWorkRules', function ($q) use ($user, $today) {
            $q->where('user_id', $user->id)
              ->where('start_date', '<=', $today)
              ->where(function ($q2) use ($today) {
                  $q2->whereNull('end_date')->orWhere('end_date', '>=', $today);
              });
        })
        ->first();

    if (!$workRule) {
        $workRule = WorkRule::where('name', '通常勤務')->first();
    }

    $missingClockOutDates = Attendance::query()
    ->where('user_id', $user->id)
    ->whereNotNull('clock_in')
    ->whereNull('clock_out')
    ->where('work_date', '<', $today) // ← 当日除外
    ->orderByDesc('work_date')
    ->limit(10)
    ->pluck('work_date')
    ->map(fn ($d) => $d->toDateString());


    // 通常の実働（退勤済みの日だけ取れる）
    $workedMinutes = ($attendance && $workRule) ? $attendance->workedMinutesForRule($workRule) : null;

    // 残業中判定：未退勤なら now() を仮の退勤として計算
    $isOvertimeNow = false;

    if ($attendance && $workRule && $attendance->clock_in && !$attendance->clock_out) {
        $date = $attendance->work_date->toDateString();

        // 所定終了時刻（work_end）
        $schedEnd = \Carbon\Carbon::parse("{$date} {$workRule->work_end}");

        // 日跨ぎ勤務（例: 22:00-05:00）なら翌日にする
        $schedStart = \Carbon\Carbon::parse("{$date} {$workRule->work_start}");
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
        // 'workedMinutesNow' => $workedMinutesNow,
        'missingClockOutDates' => $missingClockOutDates,
        'attendance' => $attendance ? [
            'id' => $attendance->id,
            'work_date' => optional($attendance->work_date)->toDateString(),

            // ここを「必ず HH:MM」に寄せる
            'clock_in'  => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : null,
            'clock_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : null,

            'note' => $attendance->note,
            'overtime_now' => $isOvertimeNow,
        ] : null,

         // ★追加（退勤ボタンの対象）
        'openAttendance' => $openAttendance ? [
            'id' => $openAttendance->id,
            'work_date' => optional($openAttendance->work_date)->toDateString(),
            'clock_in'  => $openAttendance->clock_in ? \Carbon\Carbon::parse($openAttendance->clock_in)->format('H:i') : null,
            'clock_out' => null,
        ] : null,

    ]);
})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/attendance/clock-in', [\App\Http\Controllers\AttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [\App\Http\Controllers\AttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');
});


Route::get('/admin/reports/monthly', [\App\Http\Controllers\Admin\MonthlyReportController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.reports.monthly');




Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');
});


Route::get('/admin/reports/monthly/csv', [\App\Http\Controllers\Admin\MonthlyReportController::class, 'csv'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.reports.monthly.csv');

Route::patch('/admin/attendances/{attendance}/note', [\App\Http\Controllers\Admin\AttendanceController::class, 'updateNote'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.attendances.note.update');


Route::patch('/admin/attendances/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.attendances.update');






Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // 一覧
        Route::get('/attendance-corrections', [AdminAttendanceCorrectionController::class, 'index'])
            ->name('attendance-corrections.index');

        // 承認
        Route::post('/attendance-corrections/{correction}/approve', [AdminAttendanceCorrectionController::class, 'approve'])
            ->name('attendance-corrections.approve');

        // 却下
        Route::post('/attendance-corrections/{correction}/reject', [AdminAttendanceCorrectionController::class, 'reject'])
            ->name('attendance-corrections.reject');
    });


Route::get('/admin/attendance-corrections', [\App\Http\Controllers\Admin\AttendanceCorrectionController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.attendance-corrections.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendances', [AttendanceHistoryController::class, 'index'])
        ->name('attendances.index');
});


Route::post('/attendances/{attendance}/corrections', [AttendanceCorrectionController::class, 'store'])
    ->middleware('auth')
    ->name('attendances.corrections.store');


Route::middleware(['auth', 'verified', 'admin'])
  ->prefix('admin')
  ->name('admin.')
  ->group(function () {
      Route::get('/work-rules', [\App\Http\Controllers\Admin\WorkRuleController::class, 'edit'])
          ->name('work-rules.edit');

      Route::patch('/work-rules', [\App\Http\Controllers\Admin\WorkRuleController::class, 'update'])
          ->name('work-rules.update');
  });

  Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
});

require __DIR__.'/auth.php';
