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
    /* ========== COMMON RESPONSE ========== */
    protected function sendResponse($status, $messages = [], $data = null, $code = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => (array) $messages,
            'result' => $data ?? (object)[]
        ], $code);
    }

    /* ================= SAVE ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'billNo' => 'required',
            'billDate' => 'required|date',
            'sellerName' => 'required',
            'sellerMobileNumber' => 'required',

            'subTotal' => 'required|numeric',
            'gstTotal' => 'required|numeric',
            'grossTotal' => 'required|numeric',
            'discount' => 'required|numeric',
            'grandTotal' => 'required|numeric',

            'items' => 'required|array',
            'items.*.productId' => 'required|integer',
            'items.*.qty' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.amount' => 'required|numeric',
            'items.*.gstPercent' => 'required|integer',
            'items.*.gstAmount' => 'required|numeric',
            'items.*.total' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                (object)[],
                422
            );
        }

        DB::beginTransaction();

        try {

            $businessCode = auth()->user()->business_code;

            $invoice = PurchaseInvoice::create([
                'business_code' => $businessCode,
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

                // STOCK UPDATE
                $product = Product::find($item['productId']);
                if ($product) {
                    $product->stock += $item['qty'];
                    $product->save();
                }
            }

            DB::commit();

            return $this->sendResponse(
                200,
                ['Purchase invoice saved successfully'],
                $invoice
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->sendResponse(
                500,
                ['Something went wrong'],
                $e->getMessage(),
                500
            );
        }
    }

    /* ================= GET ================= */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                (object)[],
                422
            );
        }

        $businessCode = auth()->user()->business_code;

        $invoice = PurchaseInvoice::with('items')
            ->where('business_code', $businessCode)
            ->where('id', $request->id)
            ->first();

        if (!$invoice) {
            return $this->sendResponse(
                404,
                ['Invoice not found'],
                (object)[],
                404
            );
        }

        return $this->sendResponse(
            200,
            ['Purchase invoice fetched'],
            $invoice
        );
    }

    /* ================= DELETE ================= */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                (object)[],
                422
            );
        }

        $businessCode = auth()->user()->business_code;

        $invoice = PurchaseInvoice::where('business_code', $businessCode)
            ->where('id', $request->id)
            ->first();

        if (!$invoice) {
            return $this->sendResponse(
                404,
                ['Invoice not found'],
                (object)[],
                404
            );
        }

        $invoice->items()->delete();
        $invoice->delete();

        return $this->sendResponse(
            200,
            ['Purchase invoice deleted successfully'],
            (object)[]
        );
    }

    /* ================= LIST ================= */
    public function list(Request $request)
    {
        try {

            $businessCode = auth()->user()->business_code;

            $invoices = PurchaseInvoice::with('items')
                ->where('business_code', $businessCode)
                ->orderBy('id', 'desc')
                ->get();

            return $this->sendResponse(
                200,
                ['Purchase invoice list fetched successfully'],
                $invoices
            );

        } catch (\Exception $e) {

            return $this->sendResponse(
                500,
                ['Something went wrong'],
                $e->getMessage(),
                500
            );
        }
    }
}
