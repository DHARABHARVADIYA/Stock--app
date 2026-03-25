<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GstMaster extends Model
{
    protected $table = 'gst_masters';

    protected $fillable = ['name'];

    protected $casts = [
        'id'   => 'integer',
        'name' => 'integer',
    ];
}
