<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price',
        'amount',

    ];
    
     protected $casts = [
        
        'price' => 'float',   
        'qty'   => 'integer',
        'amount' => 'float',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
   public function dispatchItems()
{
    return $this->hasMany(DispatchItem::class, 'order_item_id');
}
}
