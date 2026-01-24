<?php

use App\Http\Controllers\ProfileController;
use App\Models\Attendance;
use App\Models\WorkRule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

    $workedMinutes = null;
    if ($attendance && $workRule) {
        $workedMinutes = $attendance->workedMinutesForRule($workRule);
    }

    return Inertia::render('Dashboard', [
        'today' => $today,
        'workedMinutes' => $workedMinutes,
        'attendance' => $attendance ? [
            'id' => $attendance->id,
            'work_date' => $attendance->work_date->toDateString(),
            'clock_in' => optional($attendance->clock_in)->toISOString(),
            'clock_out' => optional($attendance->clock_out)->toISOString(),
            'note' => $attendance->note,
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


use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');
});

require __DIR__.'/auth.php';
