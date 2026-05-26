<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
          'business_code',
  'bill_no','bill_date','seller_name','seller_address',
        'seller_gst_no','seller_mobile_number',
        'sub_total','gst_total','gross_total',
        'discount','grand_total',
        'cgst','sgst','igst',
        'payment_mode','remarks'
    ];
    
     protected $casts = [
        'sub_total'   => 'float',
        'gst_total'   => 'float',
        'gross_total' => 'float',
        'discount'    => 'float',
        'grand_total' => 'float',
        'cgst'        => 'float',
        'sgst'        => 'float',
        'igst'        => 'float',
    ];


   

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }
}
