<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'postcode',
        'dob',
        'income',
        'step',
        'completed_at',
        'last_active_at'
    ];

    public function formToken() {
        return $this->hasOne(FormToken::class);
    }
}
