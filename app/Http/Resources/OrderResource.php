<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $totalDispatched = $this->items->sum(fn($item) => $item->dispatchItems->sum('dispatch_qty'));
        $totalCancelled = $this->items->sum(fn($item) => $item->dispatchItems->sum('cancel_qty'));

        return [
            'id'            => (int) $this->id,
            'site_visit_id' => (int) $this->site_visit_id,
            'bill_type_id'  => (int) $this->bill_type_id,
            'bill_date'     => $this->bill_date,

            'note' => $this->note,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'bill_total'  => (float) $this->bill_total,
            'discount'    => (float) $this->discount,
            'grand_total' => (float) $this->grand_total,

            'dispatch_status' => $this->dispatch_status,

            'delivery_person_name'   => $this->delivery_person_name,
            'delivery_person_number' => $this->delivery_person_number,
            'delivery_address'       => $this->delivery_address,


            'total_dispatched_qty' => $totalDispatched,
            'total_cancel_qty' => $totalCancelled,


            'remaining_qty' => $this->items->sum('qty') - ($totalDispatched + $totalCancelled),

            // ================= ITEMS =================
            'items' => $this->items->map(function ($item) {

                $dispatched = $item->dispatchItems->sum('dispatch_qty');
                $cancelled = $item->dispatchItems->sum('cancel_qty');

                return [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'amount' => $item->amount,

                    'total_dispatched_qty' => $dispatched,
                    'cancel_qty' => $cancelled,


                    'remaining_qty' => $item->qty - ($dispatched + $cancelled),

                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'buy_price' => $item->product->buy_price,
                        'sell_price' => $item->product->sell_price,
                        'stock' => $item->product->stock,
                        'description' => $item->product->description,
                        'image' => $item->product->image,
                    ] : null,

                    'dispatch_items' => $item->dispatchItems->map(function ($d) {
                        return [
                            'id' => $d->id,
                            'dispatch_id' => $d->dispatch_id,
                            'order_item_id' => $d->order_item_id,
                            'product_id' => $d->product_id,
                            'ordered_qty' => $d->ordered_qty,
                            'dispatched_qty' => $d->dispatch_qty,

                            'cancel_qty' => $d->cancel_qty,

                            'created_at' => $d->created_at,

                            'dispatch' => $d->dispatch ? [
                                'id' => $d->dispatch->id,
                                'transport_type' => $d->dispatch->transport_type,
                                'transport_charge' => $d->dispatch->transport_charge,
                                'vehicle_number' => $d->dispatch->vehicle_number,
                                'driver_name' => $d->dispatch->driver_name,
                                'driver_number' => $d->dispatch->driver_number,
                                'note' => $d->dispatch->note,
                            ] : null,
                        ];
                    }),
                ];
            }),

            // ================= DISPATCH =================
            'dispatch' => $this->dispatch->map(function ($d) {
                return [
                    'id' => $d->id,
                    'order_id' => $d->order_id,
                    'transport_type' => $d->transport_type,
                    'transport_charge' => $d->transport_charge,
                    'vehicle_number' => $d->vehicle_number,
                    'driver_name' => $d->driver_name,
                    'driver_number' => $d->driver_number,
                    'note' => $d->note,
                    'created_at' => $d->created_at,
                ];
            }),

            'bill_type' => $this->billType ? [
                'id' => $this->billType->id,
                'name' => $this->billType->name,
            ] : null,

            'visit' => $this->siteVisit ? [
                'id' => $this->siteVisit->id,
                'salesmen_id' => $this->siteVisit->sales_man_id,
                'salesmen_name' => $this->siteVisit->sales_man_name,
                'delivery_address' => $this->siteVisit->note,
            ] : null,
        ];
    }
}
