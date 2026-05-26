<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $table = 'site_visits';

   protected $fillable = [
    'site_id',
    'sales_man_id',
    'sales_man_name',   
    'visit_date',
    'next_visit_date',
    'order_id',
    'order_amount',
    'note',
    'visit_image',
    'latitude',
    'longitude',
];


    // Relations
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function salesMan()
    {
        return $this->belongsTo(User::class, 'sales_man_id');
    }
}
