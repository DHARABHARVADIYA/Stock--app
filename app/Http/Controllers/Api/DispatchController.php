<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use Illuminate\Support\Facades\Validator;

class DispatchController extends Controller
{

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'id' => 'required|integer',

            'order_id' => 'required|exists:orders,id',

            'status' => 'required|in:pending,in_progress,complete',

            'transport_type' => 'required|in:self,our',
            'transport_charge' => 'nullable|numeric',
            'is_charge_included_in_bill' => 'nullable|boolean',

            'delivery_person_name' => 'required',
            'delivery_person_number' => 'required',
            'delivery_address' => 'required',

            'vehicle_number' => 'required',
            'driver_name' => 'required',
            'driver_number' => 'required',

            'dispatch_datetime' => 'required|date',

            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.ordered_qty' => 'required|numeric|min:1',
            'items.*.dispatch_qty' => 'required|numeric|min:1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first()
            ]);
        }

        /* ================= ADD ================= */
        if ($request->id == 0) {

            $dispatch = Dispatch::create($request->only([
                'order_id',
                'status',
                'transport_type',
                'transport_charge',
                'is_charge_included_in_bill',
                'delivery_person_name',
                'delivery_person_number',
                'delivery_address',
                'vehicle_number',
                'driver_name',
                'driver_number',
                'dispatch_datetime',
                'latitude',
                'longitude',
                'note'
            ]));
        } else {

            /* ================= UPDATE ================= */
            $dispatch = Dispatch::find($request->id);

            if (!$dispatch) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Dispatch not found'
                ]);
            }

            $dispatch->update($request->only([
                'order_id',
                'status',
                'transport_type',
                'transport_charge',
                'is_charge_included_in_bill',
                'delivery_person_name',
                'delivery_person_number',
                'delivery_address',
                'vehicle_number',
                'driver_name',
                'driver_number',
                'dispatch_datetime',
                'latitude',
                'longitude',
                'note'
            ]));

            DispatchItem::where('dispatch_id', $dispatch->id)->delete();
        }

        /* ================= ITEMS ================= */

        $itemsResponse = [];

        foreach ($request->items as $item) {

            // total dispatched qty (same product multiple dispatch hoy to)
            $totalDispatched = DispatchItem::where('order_item_id', $item['order_item_id'])
                ->sum('dispatch_qty');

            $newTotal = $totalDispatched + $item['dispatch_qty'];

            $remaining = $item['ordered_qty'] - $newTotal;

            DispatchItem::create([
                'dispatch_id' => $dispatch->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'ordered_qty' => $item['ordered_qty'],
                'dispatch_qty' => $item['dispatch_qty']
            ]);

            $itemsResponse[] = [
                'product_id' => $item['product_id'],
                'ordered_qty' => $item['ordered_qty'],
                'dispatch_qty' => $item['dispatch_qty'],
                'total_dispatched_qty' => $newTotal,
                'remaining_qty' => $remaining,
                'is_completed' => $remaining <= 0 ? true : false
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => $request->id == 0 ? 'Dispatch created successfully' : 'Dispatch updated successfully',
            'result' => [
                'dispatch_id' => $dispatch->id,
                'order_id' => $dispatch->order_id,
                'status' => $dispatch->status,

                'vehicle_number' => $dispatch->vehicle_number,
                'dispatch_datetime' => $dispatch->dispatch_datetime,

                'transport_type' => $dispatch->transport_type,
                'transport_charge' => $dispatch->transport_charge,
                'is_charge_included_in_bill' => $dispatch->is_charge_included_in_bill,

                'latitude' => $dispatch->latitude,
                'longitude' => $dispatch->longitude,

                'items' => $itemsResponse
            ]
        ]);
    }
    public function list()
    {
        try {

            $dispatches = Dispatch::with('items')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Dispatch list fetched successfully',
                'result' => $dispatches
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'result' => $e->getMessage()
            ]);
        }
    }
}
