<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommuterAllowance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommuterAllowanceApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $query = CommuterAllowance::query()
            ->with(['user:id,name', 'approver:id,name'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $allowances = $query->get()->map(function ($allowance) {
            return [
                'id' => $allowance->id,
                'user_name' => $allowance->user?->name,
                'start_date' => optional($allowance->start_date)->toDateString(),
                'end_date' => optional($allowance->end_date)->toDateString(),
                'from_place' => $allowance->from_place,
                'to_place' => $allowance->to_place,
                'amount' => $allowance->amount,
                'pass_type' => $allowance->pass_type,
                'status' => $allowance->status,
                'note' => $allowance->note,
                'admin_comment' => $allowance->admin_comment,
                'approved_at' => optional($allowance->approved_at)?->format('Y-m-d H:i'),
                'approver_name' => $allowance->approver?->name,
                'created_at' => optional($allowance->created_at)?->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Admin/CommuterAllowances/Index', [
            'allowances' => $allowances,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function approve(Request $request, CommuterAllowance $commuterAllowance)
    {
        $validated = $request->validate([
            'admin_comment' => ['nullable', 'string'],
        ]);

        $commuterAllowance->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_comment' => $validated['admin_comment'] ?? null,
        ]);

        return back()->with('success', '通勤定期申請を承認しました。');
    }

    public function reject(Request $request, CommuterAllowance $commuterAllowance)
    {
        $validated = $request->validate([
            'admin_comment' => ['required', 'string'],
        ]);

        $commuterAllowance->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_comment' => $validated['admin_comment'],
        ]);

        return back()->with('success', '通勤定期申請を却下しました。');
    }
}