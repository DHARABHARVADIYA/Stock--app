<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'buy_price',
        'sell_price',
        'stock',
        'description',
        'image'
    ];

    protected $casts = [
        'buy_price'  => 'float',
        'sell_price' => 'float',
        'stock'      => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
