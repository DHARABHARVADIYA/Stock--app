<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use DB;

class PurchaseInvoiceController extends Controller
{
    /* ================= SAVE ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'billNo' => 'nullable',
            'billDate' => 'nullable|date',
            'sellerName' => 'nullable',
            'sellerAddress' => 'nullable',
            'sellerGstNo' => 'nullable',
            'sellerMobileNumber' => 'nullable',

            'subTotal' => 'nullable|numeric',
            'gstTotal' => 'nullable|numeric',
            'grossTotal' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'grandTotal' => 'nullable|numeric',

            'cgst' => 'nullable|numeric',
            'sgst' => 'nullable|numeric',
            'igst' => 'nullable|numeric',

            'paymentMode' => 'nullable|integer',
            'remarks' => 'nullable',

            'items' => 'nullable|array',
            'items.*.productId' => 'nullable|integer',
            'items.*.qty' => 'nullable|integer',
            'items.*.price' => 'nullable|numeric',
            'items.*.amount' => 'nullable|numeric',
            'items.*.gstPercent' => 'nullable|integer',
            'items.*.gstAmount' => 'nullable|numeric',
            'items.*.total' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'result' => $validator->errors()
            ]);
        }

        DB::beginTransaction();

        try {

            $invoice = PurchaseInvoice::create([
                'bill_no' => $request->billNo,
                'bill_date' => $request->billDate,
                'seller_name' => $request->sellerName,
                'seller_address' => $request->sellerAddress,
                'seller_gst_no' => $request->sellerGstNo,
                'seller_mobile_number' => $request->sellerMobileNumber,
                'sub_total' => $request->subTotal,
                'gst_total' => $request->gstTotal,
                'gross_total' => $request->grossTotal,
                'discount' => $request->discount,
                'grand_total' => $request->grandTotal,
                'cgst' => $request->cgst,
                'sgst' => $request->sgst,
                'igst' => $request->igst,
                'payment_mode' => $request->paymentMode,
                'remarks' => $request->remarks,
            ]);

            foreach ($request->items as $item) {

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $item['productId'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'gst_percent' => $item['gstPercent'],
                    'gst_amount' => $item['gstAmount'],
                    'total' => $item['total'],
                ]);

                // PRODUCT STOCK UPDATE
                $product = Product::find($item['productId']);
                if ($product) {
                    $product->stock = $product->stock + $item['qty'];
                    $product->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Purchase invoice saved successfully',
                'result' => $invoice
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'result' => $e->getMessage()
            ]);
        }
    }

    /* ================= GET ================= */
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:purchase_invoices,id'
        ]);

        $invoice = PurchaseInvoice::with('items')->find($request->id);

        return response()->json([
            'status' => 200,
            'message' => 'Purchase invoice fetched',
            'result' => $invoice
        ]);
    }


    /* ================= DELETE ================= */

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:purchase_invoices,id'
        ]);

        $invoice = PurchaseInvoice::find($request->id);

        // Optional: related items delete
        $invoice->items()->delete();

        $invoice->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Purchase invoice deleted successfully'
        ]);
    }


    /* ================= LIST ================= */
    public function list(Request $request)
    {
        try {

            $invoices = PurchaseInvoice::with('items')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Purchase invoice list fetched successfully',
                'result' => $invoices
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
