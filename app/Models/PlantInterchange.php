<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantInterchange extends Model
{
    protected $table = 'plant_interchanges';
    protected $fillable = ['id', 'name'];
    public $timestamps = false;
}
