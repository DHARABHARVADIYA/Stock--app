<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Business;

class BusinessController extends Controller
{
    /* ================= CREATE / UPDATE ================= */
    public function saveBusiness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'nullable|integer',
             'business_name' => 'required|string|max:255',
            'owner_name'   => 'required|string|max:255',
            'owner_number' => 'required|string|max:20',
            'gst_number'   => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'result'  => $validator->errors()
            ], 422);
        }

        $id = $request->input('id', 0);

        if ($id == 0) {
            // CREATE
            $business = Business::create($request->only([
                'business_name',
                'owner_name',
                'owner_number',
                'gst_number',
                'email'
            ]));

            $message = 'Business created successfully';
        } else {
            // UPDATE
            $business = Business::find($id);
            if (!$business) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Business not found',
                    'result'  => null
                ], 404);
            }

            $business->update($request->only([
                'business_name',
                'owner_name',
                'owner_number',
                'gst_number',
                'email'
            ]));

            $message = 'Business updated successfully';
        }

        return response()->json([
            'status'  => 200,
            'message' => $message,
            'result'  => $business
        ], 200);
    }

    /* ================= GET ALL ================= */
    public function getBusinesses()
    {
        $businesses = Business::orderBy('id', 'desc')->get();

        return response()->json([
            'status'  => 200,
            'message' => 'Business list fetched',
            'result'  => $businesses
        ], 200);
    }

    /* ================= DELETE ================= */
    public function deleteBusiness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:businesses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation error',
                'result'  => $validator->errors()
            ], 422);
        }

        Business::find($request->id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Business deleted successfully'
        ], 200);
    }
}
