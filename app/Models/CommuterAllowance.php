<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommuterAllowance extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'from_place',
        'to_place',
        'amount',
        'pass_type',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}