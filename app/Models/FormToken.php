<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormToken extends Model
{
    protected $fillable = ['token', 'customer_id'];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}
