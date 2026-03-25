<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchitectType extends Model
{
    protected $table = 'architect_types';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
