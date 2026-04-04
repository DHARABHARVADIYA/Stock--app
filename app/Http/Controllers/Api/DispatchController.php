<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;

class DispatchController extends Controller
{
    /* ========== COMMON RESPONSE ========== */
    protected function sendResponse($status, $message, $data = null, $code = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => (array) $message,
            'result' => $data ?? (object)[]
        ], $code);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'id' => 'nullable|integer',
            'order_id' => 'required|exists:orders,id',
            'status' => 'nullable|in:pending,in_progress,complete',

            'transport_type' => 'required|in:self,our',
            'transport_charge' => 'nullable|numeric|min:0',
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
            'items.*.dispatch_qty' => 'required|numeric|min:1',

        ]);

        if ($validator->fails()) {
            return $this->sendResponse(422, $validator->errors()->all(), null, 422);
        }

        $user = auth()->user();

        /* ================= TRANSPORT VALIDATION ================= */

        if ($request->transport_type == 'self') {

            if ($request->transport_charge > 0) {
                return $this->sendResponse(422, ['Transport charge must be 0 for self transport'], null, 422);
            }

            $transportCharge = 0;

        } else {

            if (!$request->transport_charge || $request->transport_charge <= 0) {
                return $this->sendResponse(422, ['Transport charge required for our transport'], null, 422);
            }

            $transportCharge = $request->transport_charge;
        }

        /* ================= ADD / UPDATE ================= */

        if (!$request->id || $request->id == 0) {

            $dispatch = Dispatch::create([
                'order_id' => $request->order_id,
                'business_code' => $user->business_code,
                'created_by' => $user->id,
                'status' => $request->status ?? 'in_progress',

                'transport_type' => $request->transport_type,
                'transport_charge' => $transportCharge,
                'is_charge_included_in_bill' => $request->is_charge_included_in_bill ?? 0,

                'delivery_person_name' => $request->delivery_person_name,
                'delivery_person_number' => $request->delivery_person_number,
                'delivery_address' => $request->delivery_address,

                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_number' => $request->driver_number,

                'dispatch_datetime' => $request->dispatch_datetime,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'note' => $request->note
            ]);

        } else {

            $dispatch = Dispatch::find($request->id);

            if (!$dispatch) {
                return $this->sendResponse(404, ['Dispatch not found'], null, 404);
            }

            $dispatch->update([
                'order_id' => $request->order_id,
                'business_code' => $user->business_code,
                'created_by' => $user->id,

                'status' => $request->status ?? $dispatch->status,

                'transport_type' => $request->transport_type,
                'transport_charge' => $transportCharge,
                'is_charge_included_in_bill' => $request->is_charge_included_in_bill ?? 0,

                'delivery_person_name' => $request->delivery_person_name,
                'delivery_person_number' => $request->delivery_person_number,
                'delivery_address' => $request->delivery_address,

                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_number' => $request->driver_number,

                'dispatch_datetime' => $request->dispatch_datetime,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'note' => $request->note
            ]);

            DispatchItem::where('dispatch_id', $dispatch->id)->delete();
        }

        /* ================= ITEMS ================= */

        $itemsResponse = [];

        foreach ($request->items as $item) {

            $orderItem = OrderItem::find($item['order_item_id']);

            if (!$orderItem) {
                return $this->sendResponse(404, ['Order item not found'], null, 404);
            }

            $orderedQty = $orderItem->qty;

            $totalDispatched = DispatchItem::where('order_item_id', $item['order_item_id'])
                ->sum('dispatch_qty');

            if ($totalDispatched >= $orderedQty) {
                return $this->sendResponse(422, ['Item already fully dispatched'], null, 422);
            }

            $newTotal = $totalDispatched + $item['dispatch_qty'];

            if ($newTotal > $orderedQty) {
                return $this->sendResponse(422, [
                    'Only ' . ($orderedQty - $totalDispatched) . ' qty remaining for dispatch'
                ], null, 422);
            }

            $remaining = $orderedQty - $newTotal;

            DispatchItem::create([
                'dispatch_id' => $dispatch->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'ordered_qty' => $orderedQty,
                'dispatch_qty' => $item['dispatch_qty']
            ]);

            $itemsResponse[] = [
                'product_id' => $item['product_id'],
                'ordered_qty' => $orderedQty,
                'dispatch_qty' => $item['dispatch_qty'],
                'total_dispatched_qty' => $newTotal,
                'remaining_qty' => $remaining,
                'is_completed' => $remaining <= 0
            ];
        }

        return $this->sendResponse(200, ['Dispatch created successfully'], [
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
        ]);
    }

    /* ================= LIST ================= */

    public function list()
    {
        try {

            $user = auth()->user();

            $query = Dispatch::with('items')->orderBy('id', 'desc');

            if ($user->role == 'admin') {
                $query->where('business_code', $user->business_code);
            }

            if (in_array($user->role, ['sales', 'dispatcher'])) {
                $query->where('created_by', $user->id);
            }

            $dispatches = $query->get();

            return $this->sendResponse(200, ['Dispatch list fetched successfully'], $dispatches);

        } catch (\Exception $e) {

            return $this->sendResponse(500, ['Something went wrong'], $e->getMessage(), 500);
        }
    }
}
