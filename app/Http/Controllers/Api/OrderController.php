<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderResource;
use App\Models\Visit;
use App\Models\SiteVisit;
use App\Models\Product;

class OrderController extends Controller
{
    // Create a new order with items
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_visit_id' => 'required|integer|exists:site_visits,id',
            'bill_type_id'  => 'required|integer|exists:bill_types,id',
            'bill_date'     => 'required|date',
            'note'          => 'nullable|string',
            'discount'      => 'nullable|numeric|min:0',
            'delivery_person_name'   => 'nullable|string|max:100',
            'delivery_person_number' => 'nullable|string|max:15',
            'delivery_address'       => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'result' => $validator->errors()
            ], 422);
        }

        $discount = $request->discount ?? 0;
        $billTotal = 0;

        /* ================= CREATE ORDER ================= */

        $order = Order::create([
            'site_visit_id' => $request->site_visit_id,
            'bill_type_id'  => $request->bill_type_id,
            'bill_date'     => $request->bill_date,
            'note'          => $request->note ?? null,
            'bill_total'    => 0,
            'discount'      => $discount,
            'grand_total'   => 0,
            'dispatch_status' => 'pending',
            'delivery_person_name'   => $request->delivery_person_name,
            'delivery_person_number' => $request->delivery_person_number,
            'delivery_address'       => $request->delivery_address,
        ]);

        /* ================= ITEMS ================= */

        foreach ($request->items as $item) {

            $product = Product::find($item['product_id']);

            if ($product->stock < $item['qty']) {
                return response()->json([
                    'status' => 400,
                    'message' => $product->name . ' stock not available',
                    'result' => null
                ], 400);
            }

            $amount = $item['qty'] * $item['price'];
            $billTotal += $amount;

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'qty'        => $item['qty'],
                'price'      => $item['price'],
                'amount'     => $amount,
            ]);

            // stock minus
            $product->stock -= $item['qty'];
            $product->save();
        }

        /* ================= TOTAL CALCULATION ================= */

        $grandTotal = $billTotal - $discount;

        $order->update([
            'bill_total'  => $billTotal,
            'grand_total' => $grandTotal
        ]);

        /* ================= UPDATE SITE VISIT ================= */

        SiteVisit::where('id', $request->site_visit_id)
            ->update([
                'order_id' => $order->id,
                'order_amount' => $grandTotal
            ]);

        $order->load('items');

        return response()->json([
            'status' => 200,
            'message' => 'Order created successfully',
            'result' => $order
        ]);
    }

    // Get all orders

    public function index()
    {
        $orders = Order::with([
            'items.dispatchItems',
            'siteVisit.site'
        ])
            ->orderBy('id', 'desc')
            ->get();

        foreach ($orders as $order) {

            $totalOrdered = 0;
            $totalDispatched = 0;

            foreach ($order->items as $item) {
                $orderedQty = $item->qty;
                $dispatchedQty = $item->dispatchItems->sum('dispatch_qty');

                $totalOrdered += $orderedQty;
                $totalDispatched += $dispatchedQty;
            }

            // status logic
            if ($totalDispatched == 0) {
                $order->dispatch_status = 'pending';
            } elseif ($totalDispatched < $totalOrdered) {
                $order->dispatch_status = 'in_progress';
            } else {
                $order->dispatch_status = 'complete';
            }

            $order->total_dispatched_qty = $totalDispatched;
            $order->remaining_qty = $totalOrdered - $totalDispatched;
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Orders fetched successfully',
            'result'  => OrderResource::collection($orders)
        ]);
    }

    // Get orders by visit
    public function getByVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_visit_id' => 'required|integer|exists:site_visits,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'result' => $validator->errors()
            ], 422);
        }

        $orders = Order::with(['items.product', 'billType', 'siteVisit.site'])
            ->where('site_visit_id', $request->site_visit_id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Orders fetched successfully',
            'result' => OrderResource::collection($orders)
        ]);
    }

    // Delete order
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'result' => $validator->errors()
            ], 422);
        }

        $order = Order::find($request->id);
        $order->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Order deleted successfully',
            'result' => null
        ]);
    }

    // Get order by ID
    public function getById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation Error',
                'result'  => $validator->errors()
            ], 422);
        }

        $order = Order::with([
            'items.product',
            'items.dispatchItems',
            'dispatch'
        ])
            ->where('id', $request->order_id)
            ->first();

        $totalOrdered = 0;
        $totalDispatched = 0;

        foreach ($order->items as $item) {

            $orderedQty = $item->qty;
            $dispatchedQty = $item->dispatchItems->sum('dispatch_qty');

            $item->total_dispatched_qty = $dispatchedQty;
            $item->remaining_qty = $orderedQty - $dispatchedQty;

            $totalOrdered += $orderedQty;
            $totalDispatched += $dispatchedQty;
        }

        // status logic
        if ($totalDispatched == 0) {
            $dispatchStatus = 'pending';
        } elseif ($totalDispatched < $totalOrdered) {
            $dispatchStatus = 'in_progress';
        } else {
            $dispatchStatus = 'complete';
        }

        $order->dispatch_status = $dispatchStatus;
        $order->total_dispatched_qty = $totalDispatched;
        $order->remaining_qty = $totalOrdered - $totalDispatched;

        return response()->json([
            'status'  => 200,
            'message' => 'Order fetched successfully',
            'result'  => $order
        ]);
    }
}
