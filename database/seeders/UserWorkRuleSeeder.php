<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserWorkRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rule = WorkRule::where('name', '通常勤務')->first();

        if (!$rule) {
            $this->command?->error('WorkRule "通常勤務" が見つかりません。先に WorkRuleSeeder を実行してください。');
            return;
        }

        $today = now()->toDateString();

        // 全ユーザーに「通常勤務」を start_date = today で割り当て
        // 既に同じ start_date で存在する場合は更新（idempotent）
        User::query()->select('id')->chunkById(200, function ($users) use ($rule, $today) {
            foreach ($users as $u) {
                DB::table('user_work_rules')->updateOrInsert(
                    [
                        'user_id' => $u->id,
                        'start_date' => $today,
                    ],
                    [
                        'work_rule_id' => $rule->id,
                        'end_date' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $this->command?->info('UserWorkRuleSeeder: 全ユーザーに通常勤務を割り当てました。');
    }
}
