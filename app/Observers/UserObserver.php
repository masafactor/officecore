<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WorkRule;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $default = WorkRule::where('name', '通常勤務')->first();
        if (!$default) return;

        $user->userWorkRules()->create([
            'work_rule_id' => $default->id,
            'start_date' => now()->toDateString(),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
