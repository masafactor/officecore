<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceClosing extends Model
{
    protected $fillable = [
        'user_id', 'year', 'month', 'status',
        'submitted_at', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool     { return $this->status === self::STATUS_DRAFT; }
    public function isSubmitted(): bool { return $this->status === self::STATUS_SUBMITTED; }
    public function isApproved(): bool  { return $this->status === self::STATUS_APPROVED; }

    public static function for(User $user, int $year, int $month): ?self
    {
        return self::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public static function statusFor(User $user, int $year, int $month): string
    {
        return self::for($user, $year, $month)?->status ?? self::STATUS_DRAFT;
    }
}