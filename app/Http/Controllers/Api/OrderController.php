<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderResource;
use App\Models\SiteVisit;
use App\Models\Product;

class OrderController extends Controller
{
    // ================= STORE =================
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
                'message' => $validator->errors()->all(),
                'result' => (object)[]
            ], 422);
        }

        $user = auth()->user();

        $siteVisit = SiteVisit::where('id', $request->site_visit_id)
            ->where('business_code', $user->business_code)
            ->first();

        if (!$siteVisit) {
            return response()->json([
                'status' => 403,
                'message' => ['Unauthorized site visit access'],
                'result' => (object)[]
            ], 403);
        }

        $discount = $request->discount ?? 0;
        $billTotal = 0;

        $order = Order::create([
            'business_code' => $user->business_code,
            'created_by'    => $user->id,
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

        foreach ($request->items as $item) {

            $product = Product::where('id', $item['product_id'])
                ->where('business_code', $user->business_code)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 403,
                    'message' => ['Unauthorized product access'],
                    'result' => (object)[]
                ], 403);
            }

            if ($product->stock < $item['qty']) {
                return response()->json([
                    'status' => 400,
                    'message' => [$product->name . ' stock not available'],
                    'result' => (object)[]
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

            $product->stock -= $item['qty'];
            $product->save();
        }

        $grandTotal = $billTotal - $discount;

        $order->update([
            'bill_total'  => $billTotal,
            'grand_total' => $grandTotal
        ]);

        SiteVisit::where('id', $request->site_visit_id)
            ->update([
                'order_id' => $order->id,
                'order_amount' => $grandTotal
            ]);

        $order->load([
            'items.product',
            'creator'
        ]);

        return response()->json([
            'status' => 200,
            'message' => ['Order created successfully'],
            'result' => new OrderResource($order)
        ]);
    }

    // ================= INDEX =================
public function index()
{
    $user = auth()->user();

    $query = Order::with([
        'items',
        'items.dispatchItems',
        'siteVisit.site',
        'creator'
    ])->where('business_code', $user->business_code);

    if ($user->role == 'sales') {
        $query->where('created_by', $user->id);
    }

    $orders = $query->orderBy('id', 'desc')->get();

    $result = $orders->map(function ($order) {

        $totalOrdered = 0;
        $totalDispatched = 0;
        $totalCancelled = 0; // ✅ NEW

        foreach ($order->items as $item) {
            $totalOrdered += $item->qty;
            $totalDispatched += $item->dispatchItems->sum('dispatch_qty');
            $totalCancelled += $item->dispatchItems->sum('cancel_qty'); // ✅ NEW
        }

        // ✅ UPDATED STATUS
        if ($totalDispatched == 0 && $totalCancelled == 0) {
            $status = 'pending';
        } elseif (($totalDispatched + $totalCancelled) < $totalOrdered) {
            $status = 'in_progress';
        } else {
            $status = 'complete';
        }

        return [
            'id' => $order->id,
            'site_visit_id' => $order->site_visit_id,
            'bill_type_id' => $order->bill_type_id,
            'bill_date' => $order->bill_date,

            'bill_total' => (float) $order->bill_total,
            'discount' => (float) $order->discount,
            'grand_total' => (float) $order->grand_total,

            'note' => $order->note,
            'dispatch_status' => $status,

            'delivery_person_name' => $order->delivery_person_name,
            'delivery_person_number' => $order->delivery_person_number,
            'delivery_address' => $order->delivery_address,

            'total_dispatched_qty' => $totalDispatched,

            'total_cancel_qty' => $totalCancelled, // ✅ NEW

            // ✅ FIXED
            'remaining_qty' => $totalOrdered - ($totalDispatched + $totalCancelled),

            'site_name' => $order->siteVisit->site->name ?? null,

            'items' => $order->items->map(function ($item) {

                $dispatched = $item->dispatchItems->sum('dispatch_qty');
                $cancelled = $item->dispatchItems->sum('cancel_qty');

                return [
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'amount' => $item->amount,

                    'cancel_qty' => $cancelled, // ✅ NEW

                    'total_dispatched_qty' => $dispatched,

                    // ✅ FIXED
                    'remaining_qty' => $item->qty - ($dispatched + $cancelled),
                ];
            })->values(),

            'salesman' => [
                'id' => $order->creator->id ?? null,
                'name' => $order->creator->name ?? null,
                'number' => $order->creator->mobile ?? null,
            ],
        ];
    });

    return response()->json([
        'status' => 200,
        'message' => ['Orders fetched successfully'],
        'result' => $result
    ]);
}

    // ================= GET BY VISIT =================
    public function getByVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_visit_id' => 'required|integer|exists:site_visits,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->all(),
                'result' => (object)[]
            ], 422);
        }

        $user = auth()->user();

        $orders = Order::with(['items.product', 'billType', 'siteVisit.site', 'creator'])
            ->where('business_code', $user->business_code)
            ->where('site_visit_id', $request->site_visit_id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => ['Orders fetched successfully'],
            'result' => OrderResource::collection($orders)
        ]);
    }

    // ================= DELETE =================
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->all(),
                'result' => (object)[]
            ], 422);
        }

        $user = auth()->user();

        $order = Order::where('business_code', $user->business_code)
            ->where('id', $request->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 404,
                'message' => ['Order not found'],
                'result' => (object)[]
            ], 404);
        }

        $order->delete();

        return response()->json([
            'status' => 200,
            'message' => ['Order deleted successfully'],
            'result' => (object)[]
        ]);
    }

    // ================= GET BY ID =================
   public function getById(Request $request)
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|integer|exists:orders,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'message' => $validator->errors()->all(),
            'result' => (object)[]
        ], 422);
    }

    $user = auth()->user();

    $order = Order::with([
        'items.product',
        'items.dispatchItems',
        'dispatch',
        'creator',
        'siteVisit.site'
    ])
        ->where('business_code', $user->business_code)
        ->where('id', $request->order_id)
        ->first();

    if (!$order) {
        return response()->json([
            'status' => 404,
            'message' => ['Order not found'],
            'result' => (object)[]
        ], 404);
    }

    return response()->json([
        'status' => 200,
        'message' => ['Order fetched successfully'],
        'result' => new OrderResource($order)
    ]);
}
}
