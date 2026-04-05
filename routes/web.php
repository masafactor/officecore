<?php

use App\Http\Controllers\Admin\AttendanceBulkPdfController;
use App\Http\Controllers\ProfileController;
use App\Models\Attendance;
use App\Models\WorkRule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AttendanceCorrectionController as AdminAttendanceCorrectionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CommuterAllowanceApprovalController;
use App\Http\Controllers\Admin\CompanyCalendarController;
use App\Http\Controllers\Admin\EmployeePayrollController;
use App\Http\Controllers\Admin\PartTimePayrollController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WageTableController;
use App\Http\Controllers\AttendanceHistoryController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\AttendanceClosingController;
use App\Http\Controllers\AttendancePdfController;
use App\Http\Controllers\CommuterAllowanceController;
use App\Http\Controllers\DailyReportPdfController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


// Route::get('/dashboard', function () {
//     $user = request()->user();
//     $today = now()->toDateString();

//     $attendance = Attendance::where('user_id', $user->id)
//         ->where('work_date', $today)
//         ->first();

//     $workRule = WorkRule::query()
//         ->whereHas('userWorkRules', function ($q) use ($user, $today) {
//             $q->where('user_id', $user->id)
//               ->where('start_date', '<=', $today)
//               ->where(function ($q2) use ($today) {
//                   $q2->whereNull('end_date')->orWhere('end_date', '>=', $today);
//               });
//         })
//         ->first();

//     if (!$workRule) {
//         $workRule = WorkRule::where('name', '通常勤務')->first();
//     }

//     $missingClockOutDates = Attendance::query()
//     ->where('user_id', $user->id)
//     ->whereNotNull('clock_in')
//     ->whereNull('clock_out')
//     ->where('work_date', '<', $today) // ← 当日除外
//     ->orderByDesc('work_date')
//     ->limit(10)
//     ->pluck('work_date')
//     ->map(fn ($d) => $d->toDateString());


//     // 通常の実働（退勤済みの日だけ取れる）
//     $workedMinutes = ($attendance && $workRule) ? $attendance->totalMinutesForRule($workRule) : null;

//     // 残業中判定：未退勤なら now() を仮の退勤として計算
//     $isOvertimeNow = false;

//     if ($attendance && $workRule && $attendance->clock_in && !$attendance->clock_out) {
//         $date = $attendance->work_date->toDateString();

//         // 所定終了時刻（work_end）
//         $schedEnd = \Carbon\Carbon::parse("{$date} {$workRule->work_end}");

//         // 日跨ぎ勤務（例: 22:00-05:00）なら翌日にする
//         $schedStart = \Carbon\Carbon::parse("{$date} {$workRule->work_start}");
//         if ($schedEnd->lte($schedStart)) {
//             $schedEnd->addDay();
//         }

//         $isOvertimeNow = now()->gt($schedEnd);
//     }



//     $openAttendance = Attendance::query()
//     ->where('user_id', $user->id)
//     ->whereNotNull('clock_in')
//     ->whereNull('clock_out')
//     ->orderByDesc('work_date')
//     ->first();


//     return Inertia::render('Dashboard', [
//         'today' => $today,
//         'workedMinutes' => $workedMinutes,
//         // 'workedMinutesNow' => $workedMinutesNow,
//         'missingClockOutDates' => $missingClockOutDates,
//         'attendance' => $attendance ? [
//             'id' => $attendance->id,
//             'work_date' => optional($attendance->work_date)->toDateString(),

//             // ここを「必ず HH:MM」に寄せる
//             'clock_in'  => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : null,
//             'clock_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : null,

//             'note' => $attendance->note,
//             'overtime_now' => $isOvertimeNow,
//         ] : null,

//          // ★追加（退勤ボタンの対象）
//         'openAttendance' => $openAttendance ? [
//             'id' => $openAttendance->id,
//             'work_date' => optional($openAttendance->work_date)->toDateString(),
//             'clock_in'  => $openAttendance->clock_in ? \Carbon\Carbon::parse($openAttendance->clock_in)->format('H:i') : null,
//             'clock_out' => null,
//         ] : null,

//     ]);
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');



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

      Route::patch('/work-rules/{workRule}', [\App\Http\Controllers\Admin\WorkRuleController::class, 'update'])
          ->name('work-rules.update');

              Route::patch('/users/{user}/employment', [\App\Http\Controllers\Admin\UserController::class, 'updateEmployment'])
          ->name('users.employment.update');
  });

  Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}/work-rule', [\App\Http\Controllers\Admin\UserController::class, 'updateWorkRule'])->name('users.work-rule.update');
});




Route::middleware(['auth'])->group(function () {
    // 一般ユーザー：提出・取り消し
    Route::post('/attendance/closing/submit', [AttendanceClosingController::class, 'submit'])
        ->name('attendance.closing.submit');
    Route::post('/attendance/closing/cancel', [AttendanceClosingController::class, 'cancel'])
        ->name('attendance.closing.cancel');

    Route::middleware(['admin'])->group(function () {
        // 管理画面表示
        Route::get('/admin/attendance/closings', [\App\Http\Controllers\Admin\AttendanceClosingController::class, 'index'])
            ->name('admin.attendance.closings.index');

        // 管理者：承認・承認解除
        Route::post('/admin/attendance/closing/approve', [AttendanceClosingController::class, 'approve'])
            ->name('admin.attendance.closing.approve');
        Route::post('/admin/attendance/closing/unapprove', [AttendanceClosingController::class, 'unapprove'])
            ->name('admin.attendance.closing.unapprove');

        Route::get('/admin/attendance/closings/{user}/{year}/{month}', [\App\Http\Controllers\Admin\AttendanceClosingController::class, 'show'])
            ->whereNumber('user')
            ->whereNumber('year')
            ->whereNumber('month')
            ->name('admin.attendance.closings.show');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/pdf/{year}/{month}', [AttendancePdfController::class, 'downloadMyMonthly'])
        ->whereNumber('year')
        ->whereNumber('month')
        ->name('attendance.pdf.monthly');

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/attendance/closings/{user}/{year}/{month}/pdf', [AttendancePdfController::class, 'downloadMonthly'])
            ->whereNumber('user')
            ->whereNumber('year')
            ->whereNumber('month')
            ->name('admin.attendance.closings.pdf');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/pdf/{year}/{month}', [AttendancePdfController::class, 'downloadMyMonthly'])
        ->whereNumber('year')
        ->whereNumber('month')
        ->name('attendance.pdf.monthly');

});



Route::get(
    '/admin/attendance-request-details/{user}/{year}/{month}/pdf',
    [\App\Http\Controllers\Admin\AttendancePdfController::class, 'downloadMonthlyRequestDetail']
)->name('admin.attendance-request-details.pdf');

Route::middleware(['auth'])->group(function () {
    Route::post(
        '/admin/monthly-approvals/{year}/{month}/bulk-pdf',
        [AttendanceBulkPdfController::class, 'download']
    )->name('admin.monthly-approvals.bulk-pdf');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/daily-reports/pdf/weekly', [DailyReportPdfController::class, 'downloadWeekly'])
        ->name('daily-reports.pdf.weekly');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/daily-reports', [\App\Http\Controllers\Admin\DailyReportController::class, 'index'])
        ->name('daily-reports.index');
        
       Route::get('/daily-reports/pdf', [App\Http\Controllers\Admin\DailyReportBulkPdfController::class, 'index'])
        ->name('daily-reports.pdf.index');

    Route::get('/daily-reports/{dailyReport}', [\App\Http\Controllers\Admin\DailyReportController::class, 'show'])
        ->name('daily-reports.show');

    Route::get('/daily-reports/{dailyReport}/pdf', [App\Http\Controllers\Admin\DailyReportPdfController::class, 'show'])
        ->name('daily-reports.pdf.show');

    Route::get('/wage-tables', [WageTableController::class, 'index'])
        ->name('wage-tables.index');

    Route::get('/wage-tables/create', [WageTableController::class, 'create'])
        ->name('wage-tables.create');

    Route::post('/wage-tables', [WageTableController::class, 'store'])
        ->name('wage-tables.store');
    Route::get('/wage-tables/{wageTable}/edit', [WageTableController::class, 'edit'])
        ->name('wage-tables.edit');

    Route::put('/wage-tables/{wageTable}', [WageTableController::class, 'update'])
        ->name('wage-tables.update');

    Route::delete('/wage-tables/{wageTable}', [WageTableController::class, 'destroy'])
    ->name('wage-tables.destroy');

    Route::get('/payrolls/part-time', [PartTimePayrollController::class, 'index'])
    ->name('payrolls.part-time.index');

    Route::get('/payrolls/part-time/csv', [PartTimePayrollController::class, 'csv'])
    ->name('payrolls.part-time.csv');

    Route::post('/users/{user}/salary', [UserController::class, 'updateSalary'])
    ->name('users.update-salary');

    Route::get('/payrolls/employees', [EmployeePayrollController::class, 'index'])
    ->name('payrolls.employees.index');

    Route::get('/payrolls/employees/csv', [EmployeePayrollController::class, 'csv'])
    ->name('payrolls.employees.csv');

    Route::get('/company-calendar', [CompanyCalendarController::class, 'index'])
    ->name('company-calendar.index');

    Route::post('/company-calendar', [CompanyCalendarController::class, 'store'])
        ->name('company-calendar.store');

    Route::patch('/company-calendar/{companyCalendarDay}', [CompanyCalendarController::class, 'update'])
        ->name('company-calendar.update');

    Route::post('/company-calendar/generate-year', [CompanyCalendarController::class, 'generateYear'])
        ->name('company-calendar.generate-year');

    Route::post('/company-calendar/bulk-update', [CompanyCalendarController::class, 'bulkUpdate'])
    ->name('company-calendar.bulk-update');

    Route::post('/company-calendar/update-weekdays', [CompanyCalendarController::class, 'updateWeekdays'])
    ->name('company-calendar.update-weekdays');

    Route::post('/company-calendar/generate-holidays', [CompanyCalendarController::class, 'generateHolidays'])
    ->name('company-calendar.generate-holidays');

    Route::post('/company-calendar/generate-substitute-holidays', [CompanyCalendarController::class, 'generateSubstituteHolidays'])
    ->name('company-calendar.generate-substitute-holidays');

    Route::get('/commuter-allowances', [CommuterAllowanceApprovalController::class, 'index'])
        ->name('commuter-allowances.index');

    Route::post('/commuter-allowances/{commuterAllowance}/approve', [CommuterAllowanceApprovalController::class, 'approve'])
        ->name('commuter-allowances.approve');

    Route::post('/commuter-allowances/{commuterAllowance}/reject', [CommuterAllowanceApprovalController::class, 'reject'])
        ->name('commuter-allowances.reject');

 
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/commuter-allowances', [CommuterAllowanceController::class, 'index'])
        ->name('commuter-allowances.index');

    Route::get('/commuter-allowances/create', [CommuterAllowanceController::class, 'create'])
        ->name('commuter-allowances.create');

    Route::post('/commuter-allowances', [CommuterAllowanceController::class, 'store'])
        ->name('commuter-allowances.store');
});



require __DIR__.'/auth.php';
