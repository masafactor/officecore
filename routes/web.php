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
            'clock_in'  => optional($attendance->clock_in)->format('H:i'),
            'clock_out' => optional($attendance->clock_out)->format('H:i'),
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





// Route::middleware(['auth'])->group(function () {
//     // 従業員：申請
//     Route::post('/attendances/{attendance}/corrections', [AttendanceCorrectionController::class, 'store'])
//         ->name('attendances.corrections.store');
// });

// Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
//     // 管理者：申請一覧
//     Route::get('/attendance-corrections', [AdminAttendanceCorrectionController::class, 'index'])
//         ->name('attendance-corrections.index');
// });

Route::get('/admin/attendance-corrections', [\App\Http\Controllers\Admin\AttendanceCorrectionController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.attendance-corrections.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendances', [AttendanceHistoryController::class, 'index'])
        ->name('attendances.index');
});
    
require __DIR__.'/auth.php';
