<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

   protected $fillable = [
    'business_code',
    'created_by',
    'site_visit_id',
    'bill_type_id',
    'bill_date',
    'note',

    'discount',

   'bill_total',
    'grand_total',
    'dispatch_status',

    'delivery_person_name',
    'delivery_person_number',
    'delivery_address',

    // optional (if used)
    'total',
    'amount',
    'gst_percent',
];



    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

     public function billType()
    {
        return $this->belongsTo(BillType::class, 'bill_type_id');
    }

     public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function siteVisit()
    {
        return $this->belongsTo(\App\Models\SiteVisit::class, 'site_visit_id');
    }

    public function dispatch()
    {
        return $this->hasMany(Dispatch::class,'order_id');
    }

     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
