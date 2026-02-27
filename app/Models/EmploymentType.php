<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentType extends Model
{
    protected $fillable = ['code', 'name'];

    public function userEmployments(): HasMany
    {
        return $this->hasMany(UserEmployment::class);
    }
}