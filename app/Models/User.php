<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userWorkRules()
    {
        return $this->hasMany(\App\Models\UserWorkRule::class);
    }

    public function dailyReports()
    {
        return $this->hasMany(\App\Models\DailyReport::class);
    }

    public function currentWorkRule(Carbon|string|null $date = null): ?WorkRule
    {
        $date = $date ? Carbon::parse($date) : now();

        $history = $this->userWorkRules()
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->with('workRule')
            ->first();

        // ✅ 未割当フォールバック（通常勤務）
        return $history?->workRule
            ?? WorkRule::where('name', '通常勤務')->first();
    }

}
