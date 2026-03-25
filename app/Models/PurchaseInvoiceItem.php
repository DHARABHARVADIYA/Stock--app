<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id','product_id','qty',
        'price','amount','gst_percent','gst_amount','total'
    ];

    public $timestamps = false;
}
