<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id','product_id','qty',
        'price','amount','gst_percent','gst_amount','total','item_discount'
    ];

    public $timestamps = false;
    
      protected $casts = [
        'qty'        => 'integer',
        'price'      => 'float',
        'amount'     => 'float',
        'gst_percent'=> 'integer',
        'gst_amount' => 'float',
        'total'      => 'float',
    ];
}
