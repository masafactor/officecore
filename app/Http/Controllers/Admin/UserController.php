<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkRule;
use App\Models\UserWorkRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'role')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['userWorkRules.workRule' => fn ($q) => $q->select('id', 'name')]);

        $workRules = WorkRule::query()
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        // 現在有効（今日 기준）
        $current = $user->currentWorkRule(now());

        // 履歴（新しい順）
        $histories = $user->userWorkRules()
            ->with('workRule:id,name')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'work_rule_id' => $h->work_rule_id,
                'work_rule_name' => $h->workRule?->name,
                'start_date' => $h->start_date?->toDateString(),
                'end_date' => $h->end_date?->toDateString(),
            ]);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'staff',
            ],
            'workRules' => $workRules,
            'currentWorkRule' => $current ? ['id' => $current->id, 'name' => $current->name] : null,
            'histories' => $histories,
        ]);
    }

    public function updateWorkRule(Request $request, User $user)
    {
        $validated = $request->validate([
            'work_rule_id' => ['required', 'exists:work_rules,id'],
            'start_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();

        // ① 開始日以降に既存履歴があるなら、最小版では弾く（事故防止）
        $conflict = $user->userWorkRules()
            ->where('start_date', '>=', $start->toDateString())
            ->exists();

        if ($conflict) {
            return back()->with('error', 'その開始日以降に既存の勤務ルール履歴があります。開始日を見直してください。');
        }

        // ② 直前の履歴（開始日より前で一番新しい）を閉じる
        $prev = $user->userWorkRules()
            ->where('start_date', '<=', $start->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if ($prev) {
            // prev が start_date 以降も有効なら end_date を閉じる
            if ($prev->end_date === null || Carbon::parse($prev->end_date)->gte($start)) {
                $prev->end_date = $start->copy()->subDay()->toDateString();
                $prev->save();
            }
        }

        // ③ 新しい履歴を追加（end_date=null）
        $user->userWorkRules()->create([
            'work_rule_id' => (int)$validated['work_rule_id'],
            'start_date'   => $start->toDateString(),
            'end_date'     => null,
        ]);

        return back()->with('success', '勤務ルールを割り当てました。');
    }
}