<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'access_token',
        'address',
        'postcode',
        'dob',
        'income',
        'step',
        'completed_at',
        'last_active_at'
    ];
}
