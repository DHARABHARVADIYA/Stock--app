<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{

protected $table = 'dispatch';

protected $fillable = [

    'order_id',
    'status',

    'transport_type',
    'transport_charge',
    'is_charge_included_in_bill',

    'delivery_person_name',
    'delivery_person_number',
    'delivery_address',

    'vehicle_number',
    'driver_name',
    'driver_number',

    'dispatch_datetime',

    'latitude',
    'longitude',

    'note'
];

public function items()
{
return $this->hasMany(DispatchItem::class,'dispatch_id');
}

}
