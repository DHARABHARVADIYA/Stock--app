<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'refresh_token',
        'expires_at'
    ];

    protected $dates = ['expires_at'];
}
