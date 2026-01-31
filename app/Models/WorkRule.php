<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkRule extends Model
{
    protected $fillable = [
        'name',
        'work_start',
        'work_end',
        'break_start',
        'break_end',
    ];

       protected $casts = [
        'work_start'  => 'string',
        'work_end'    => 'string',
        'break_start' => 'string',
        'break_end'   => 'string',
    ];

    /**
     * この勤務ルールを使っているユーザー履歴
     */
    public function userWorkRules(): HasMany
    {
        return $this->hasMany(UserWorkRule::class);
    }

    /**
     * 固定休憩時間（分）
     */
    public function breakMinutes(): int
    {
        return $this->break_end->diffInMinutes($this->break_start);
    }
}
