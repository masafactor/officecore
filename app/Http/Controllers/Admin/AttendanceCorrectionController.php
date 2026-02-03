<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use Inertia\Inertia;

class AttendanceCorrectionController extends Controller
{
    public function index()
    {
        $corrections = AttendanceCorrection::query()
            ->with(['attendance.user', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20)
            ->through(fn ($c) => [
                'id' => $c->id,
                'status' => $c->status,
                'reason' => $c->reason,
                'clock_in_at' => optional($c->clock_in_at)->toDateTimeString(),
                'clock_out_at' => optional($c->clock_out_at)->toDateTimeString(),
                'note' => $c->note,
                'created_at' => optional($c->created_at)->toDateTimeString(),

                'attendance' => $c->attendance ? [
                    'id' => $c->attendance->id,
                    'work_date' => optional($c->attendance->work_date)->toDateString(),
                    'clock_in' => $c->attendance->clock_in ? substr((string) $c->attendance->clock_in, 0, 5) : null,
                    'clock_out' => $c->attendance->clock_out ? substr((string) $c->attendance->clock_out, 0, 5) : null,
                ] : null,

                'user' => $c->attendance?->user ? [
                    'id' => $c->attendance->user->id,
                    'name' => $c->attendance->user->name,
                    'email' => $c->attendance->user->email,
                ] : null,

                'requester' => $c->requester ? [
                    'id' => $c->requester->id,
                    'name' => $c->requester->name,
                ] : null,
            ]);

        return Inertia::render('Admin/AttendanceCorrections/Index', [
            'corrections' => $corrections,
        ]);
    }
}
