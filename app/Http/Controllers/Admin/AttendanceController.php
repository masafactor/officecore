<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $attendances = Attendance::query()
            ->with(['user:id,name,email'])
            ->where('work_date', $date)
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        // Inertiaには必要項目だけ渡す（TSで扱いやすくする）
        $items = $attendances->through(function (Attendance $a) {
            return [
                'id' => $a->id,
                'work_date' => $a->work_date->toDateString(),
                'clock_in' => optional($a->clock_in)->toISOString(),
                'clock_out' => optional($a->clock_out)->toISOString(),
                'note' => $a->note,
                'user' => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'email' => $a->user->email,
                ],
            ];
        });

        return Inertia::render('Admin/Attendances/Index', [
            'filters' => [
                'date' => $date,
            ],
            'attendances' => [
                'data' => $items,
                'links' => $attendances->linkCollection(),
                'total' => $attendances->total(),
            ],
        ]);
    }
}
