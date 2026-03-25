<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    protected $table = 'payment_modes';

    protected $fillable = ['name', 'image'];

    protected $casts = [
        'id' => 'integer',
    ];
}
