<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => (int) $this->id,
            'site_visit_id' => (int) $this->site_visit_id,
            'bill_type_id'  => (int) $this->bill_type_id,
            'bill_date'     => $this->bill_date,

            'bill_total'  => (float) $this->bill_total,
            'discount'    => (float) $this->discount,
            'grand_total' => (float) $this->grand_total,

            'note' => $this->note,

            'dispatch_status' => $this->dispatch_status,

            'delivery_person_name'   => $this->delivery_person_name,
            'delivery_person_number' => $this->delivery_person_number,
            'delivery_address'       => $this->delivery_address,

            'total_dispatched_qty' => $this->items->sum(function ($item) {
                return $item->dispatchItems->sum('dispatch_qty');
            }),

            'remaining_qty' => $this->items->sum('qty') -
                $this->items->sum(function ($item) {
                    return $item->dispatchItems->sum('dispatch_qty');
                }),


            'site_name' => optional(optional($this->siteVisit)->site)->name,

            'items' => $this->items->map(function ($item) {
                return [
                    'product_id' => (int) $item->product_id,
                    'qty'        => (int) $item->qty,
                    'price'      => (float) $item->price,
                    'amount'     => (float) $item->amount
                ];
            })->values(),

            'salesman' => [
                'id'     => (int) optional($this->creator)->id,
                'name'   => optional($this->creator)->name,
                'number' => optional($this->creator)->mobile_number,
            ],
        ];
    }
}
