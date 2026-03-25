<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstructionStage extends Model
{
    protected $table = 'construction_stages';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
