<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasonType extends Model
{
    protected $table = 'mason_types';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
