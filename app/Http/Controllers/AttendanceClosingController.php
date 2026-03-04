<?php

namespace App\Http\Controllers;

use App\Models\AttendanceClosing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceClosingController extends Controller
{
    private function validateYm(Request $request): array
    {
        return $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);
    }

    // 一般ユーザー：その月を提出
    public function submit(Request $request)
    {
        $user = $request->user();
        $ym = $this->validateYm($request);

        // 「先月だけ提出可」ルール（任意：あなたが言ってた運用）
        // 今日が 2026-03-04 なら提出対象は 2026-02
        $now = Carbon::now();
        $prev = $now->copy()->subMonthNoOverflow();
        if (!($ym['year'] == (int)$prev->year && $ym['month'] == (int)$prev->month)) {
            return back()->with('error', '提出できるのは先月分のみです。');
        }

        DB::transaction(function () use ($user, $ym) {
            AttendanceClosing::query()->updateOrCreate(
                ['user_id' => $user->id, 'year' => $ym['year'], 'month' => $ym['month']],
                ['status' => AttendanceClosing::STATUS_SUBMITTED, 'submitted_at' => now()]
            );
        });

        return back()->with('success', '勤怠を提出しました。');
    }

    // 一般ユーザー：提出取り消し（submitted → draft）
    public function cancel(Request $request)
    {
        $user = $request->user();
        $ym = $this->validateYm($request);

        $closing = AttendanceClosing::query()
            ->where('user_id', $user->id)
            ->where('year', $ym['year'])
            ->where('month', $ym['month'])
            ->first();

        // 行が無い=そもそもdraft。何もしないでOK
        if (!$closing) {
            return back()->with('success', '取り消し済みです。');
        }

        if ($closing->status !== AttendanceClosing::STATUS_SUBMITTED) {
            return back()->with('error', '取り消しできるのは提出済みの月だけです。');
        }

        $closing->update([
            'status' => AttendanceClosing::STATUS_DRAFT,
            'submitted_at' => null,
        ]);

        return back()->with('success', '提出を取り消しました。');
    }

    // 管理者：承認（submitted → approved）
    public function approve(Request $request)
    {
        $ym = $this->validateYm($request);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $admin = $request->user();

        $closing = AttendanceClosing::query()->firstOrCreate(
            ['user_id' => $data['user_id'], 'year' => $ym['year'], 'month' => $ym['month']],
            ['status' => AttendanceClosing::STATUS_DRAFT]
        );

        if ($closing->status !== AttendanceClosing::STATUS_SUBMITTED) {
            return back()->with('error', '承認できるのは提出済みの月だけです。');
        }

        $closing->update([
            'status' => AttendanceClosing::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        return back()->with('success', '承認しました。');
    }

    // 管理者：承認解除（approved → submitted）
    public function unapprove(Request $request)
    {
        $ym = $this->validateYm($request);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $closing = AttendanceClosing::query()
            ->where('user_id', $data['user_id'])
            ->where('year', $ym['year'])
            ->where('month', $ym['month'])
            ->first();

        if (!$closing || $closing->status !== AttendanceClosing::STATUS_APPROVED) {
            return back()->with('error', '承認解除できるのは承認済みの月だけです。');
        }

        $closing->update([
            'status' => AttendanceClosing::STATUS_SUBMITTED,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', '承認を解除しました。');
    }
}