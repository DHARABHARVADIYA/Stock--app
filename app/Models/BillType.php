<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillType extends Model
{
    protected $table = 'bill_types';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
