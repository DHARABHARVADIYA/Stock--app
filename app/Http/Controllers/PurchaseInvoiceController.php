<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use DB;

class PurchaseInvoiceController extends Controller
{
    /* ================= SAVE ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billNo' => 'required',
            'billDate' => 'required|date',
            'sellerName' => 'required',
            'sellerAddress' => 'required',
            'sellerGstNo' => 'required',
            'sellerMobileNumber' => 'required',

            'subTotal' => 'required|numeric',
            'gstTotal' => 'required|numeric',
            'grossTotal' => 'required|numeric',
            'discount' => 'required|numeric',
            'grandTotal' => 'required|numeric',

            'cgst' => 'required|numeric',
            'sgst' => 'required|numeric',
            'igst' => 'required|numeric',

            'paymentMode' => 'required|integer',
            'remarks' => 'required',

            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|integer',
            'items.*.qty' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.amount' => 'required|numeric',
            'items.*.gstPercent' => 'required|integer',
            'items.*.gstAmount' => 'required|numeric',
            'items.*.total' => 'required|numeric',
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
    public function show($id)
    {
        $invoice = PurchaseInvoice::with('items')->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 404,
                'message' => 'Invoice not found'
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Invoice fetched',
            'result' => $invoice
        ]);
    }

    /* ================= DELETE ================= */
    public function delete($id)
    {
        $invoice = PurchaseInvoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status' => 404,
                'message' => 'Invoice not found'
            ]);
        }

        $invoice->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Invoice deleted successfully'
        ]);
    }
}
