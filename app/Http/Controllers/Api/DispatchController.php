<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class DispatchController extends Controller
{
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

            'transport_type' => 'required|in:self,our',
            'transport_charge' => 'nullable|numeric|min:0',

            'delivery_person_name' => 'required',
            'delivery_person_number' => 'required',
            'delivery_address' => 'required',

            'vehicle_number' => 'required',
            'driver_name' => 'required',
            'driver_number' => 'required',

            'dispatch_datetime' => 'required|date',

            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.dispatch_qty' => 'required|numeric|min:1',
            'items.*.cancel_qty' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(422, $validator->errors()->all(), null, 422);
        }

        $user = auth()->user();

        /* ================= TRANSPORT ================= */

        if ($request->transport_type == 'self') {
            if ($request->transport_charge > 0) {
                return $this->sendResponse(422, ['Transport charge must be 0 for self'], null, 422);
            }
            $transportCharge = 0;
        } else {
            if (!$request->transport_charge || $request->transport_charge <= 0) {
                return $this->sendResponse(422, ['Transport charge required'], null, 422);
            }
            $transportCharge = $request->transport_charge;
        }

        /* ================= CREATE / UPDATE ================= */

        if (!$request->id || $request->id == 0) {

            $dispatch = Dispatch::create([
                'order_id' => $request->order_id,
                'business_code' => $user->business_code,
                'created_by' => $user->id,

                'transport_type' => $request->transport_type,
                'transport_charge' => $transportCharge,

                'delivery_person_name' => $request->delivery_person_name,
                'delivery_person_number' => $request->delivery_person_number,
                'delivery_address' => $request->delivery_address,

                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_number' => $request->driver_number,

                'dispatch_datetime' => $request->dispatch_datetime,
                'note' => $request->note
            ]);

        } else {

            $dispatch = Dispatch::find($request->id);

            if (!$dispatch) {
                return $this->sendResponse(404, ['Dispatch not found'], null, 404);
            }

            $dispatch->update([
                'order_id' => $request->order_id,

                'transport_type' => $request->transport_type,
                'transport_charge' => $transportCharge,

                'delivery_person_name' => $request->delivery_person_name,
                'delivery_person_number' => $request->delivery_person_number,
                'delivery_address' => $request->delivery_address,

                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_number' => $request->driver_number,

                'dispatch_datetime' => $request->dispatch_datetime,
                'note' => $request->note
            ]);

            // old items delete
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
            $cancelQty = $item['cancel_qty'] ?? 0;

            // ðŸ‘‰ ONLY cancel validation
            $totalCancelled = DispatchItem::where('order_item_id', $item['order_item_id'])
                ->sum('cancel_qty');

            if (($totalCancelled + $cancelQty) > $orderedQty) {
                return $this->sendResponse(422, [
                    'Cancel qty exceeds ordered qty. Remaining: ' . ($orderedQty - $totalCancelled)
                ], null, 422);
            }

            DispatchItem::create([
                'dispatch_id' => $dispatch->id,
                'order_item_id' => $item['order_item_id'],
                'product_id' => $item['product_id'],
                'ordered_qty' => $orderedQty,
                'dispatch_qty' => $item['dispatch_qty'], // untouched
                'cancel_qty' => $cancelQty
            ]);

            // âœ… ONLY cancel stock add
            if ($cancelQty > 0) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock += $cancelQty;
                    $product->save();
                }
            }

            $itemsResponse[] = [
                'product_id' => $item['product_id'],
                'ordered_qty' => $orderedQty,
                'dispatch_qty' => $item['dispatch_qty'],
                'cancel_qty' => $cancelQty
            ];
        }

        return $this->sendResponse(200, ['Dispatch saved successfully'], [
            'dispatch_id' => $dispatch->id,
            'order_id' => $dispatch->order_id,
            'transport_type' => $dispatch->transport_type,
            'transport_charge' => $dispatch->transport_charge,
            'items' => $itemsResponse
        ]);
    }

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
