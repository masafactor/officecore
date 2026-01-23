<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWorkRule extends Model
{
    protected $fillable = [
        'user_id',
        'work_rule_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤務ルール
     */
    public function workRule(): BelongsTo
    {
        return $this->belongsTo(WorkRule::class);
    }

    /**
     * 指定日に有効かどうか
     */
    public function isActiveOn(string $date): bool
    {
        if ($this->start_date->gt($date)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($date)) {
            return false;
        }

        return true;
    }
}
