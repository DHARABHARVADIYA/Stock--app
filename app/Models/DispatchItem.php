<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchItem extends Model
{

protected $table = 'dispatch_items';

protected $fillable = [

'dispatch_id',
'order_item_id',
'product_id',
'ordered_qty',
'dispatch_qty',
 'cancel_qty'

];

}
