<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteVisit;
use App\Models\User;
use Validator;
use Carbon\Carbon;

class SiteVisitController extends Controller
{

    // ================= Save / Update =================
    public function save(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [

            'site_id' => 'required|exists:sites,id',
            'sales_man_id' => 'nullable|exists:users,id',
            'visit_date' => 'required|date',
            'next_visit_date' => 'required|date|after_or_equal:visit_date',
            'order_id' => 'nullable|exists:orders,id',
            'order_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
            'visit_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->id) {

            $query = SiteVisit::where('id', $request->id);

            if ($user->role == 'admin') {
                $query->where('business_code', $user->business_code);
            } else if ($user->role == 'sales') {
                $query->where('created_by', $user->id);
            }

            $visit = $query->first();

            if (!$visit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Visit not found'
                ]);
            }
        } else {
            $visit = new SiteVisit();


            $visit->created_by = $user->id;
            $visit->business_code = $user->business_code;
        }

        $visit->fill([
            'site_id' => $request->site_id,
            'sales_man_id' => $request->sales_man_id,
            'sales_man_name' => $request->sales_man_name,
            'visit_date' => $request->visit_date,
            'next_visit_date' => $request->next_visit_date,
            'order_id' => $request->order_id,
            'order_amount' => $request->order_amount,
            'note' => $request->note,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Image Upload
        if ($request->hasFile('visit_image')) {

            $image = $request->file('visit_image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            $image->move(public_path('visits'), $imageName);

            $visit->visit_image = 'visits/' . $imageName;
        }

        $visit->save();

        return response()->json([
            'status' => true,
            'message' => 'Site visit saved successfully',
            'data' => $this->formatVisit($visit)
        ]);
    }


    // ================= Get All =================
    public function index()
    {
        $user = auth('api')->user();

        $query = SiteVisit::query();

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $visits = $query->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $visits->map(function ($visit) {
                return $this->formatVisit($visit);
            })
        ]);
    }


    // ================= Get By Site =================
    public function getBySite(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = SiteVisit::where('site_id', $request->site_id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $visits = $query->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $visits->map(function ($visit) {
                return $this->formatVisit($visit);
            })
        ]);
    }


    // ================= Delete =================
    public function delete(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:site_visits,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = SiteVisit::where('id', $request->id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $visit = $query->first();

        if (!$visit) {
            return response()->json([
                'status' => false,
                'message' => 'Visit not found'
            ]);
        }

        if ($visit->visit_image && file_exists(public_path($visit->visit_image))) {
            unlink(public_path($visit->visit_image));
        }

        $visit->delete();

        return response()->json([
            'status' => true,
            'message' => 'Visit deleted successfully'
        ]);
    }


    // ================= Format Response =================
    private function formatVisit($visit)
    {
        return [
            "id" => (int) $visit->id,
            "siteId" => (int) $visit->site_id,
            "salesManId" => $visit->sales_man_id ? (int) $visit->sales_man_id : null,
            "salesManName" => $visit->sales_man_name,
            "date" => Carbon::parse($visit->visit_date)->format('Y-m-d'),
            "nextVisitDate" => Carbon::parse($visit->next_visit_date)->format('Y-m-d'),
            "orderId" => $visit->order_id ? (int) $visit->order_id : null,
            "orderAmount" => $visit->order_amount ? (float) $visit->order_amount : null,
            "note" => $visit->note,
            "visitImage" => $visit->visit_image,
            "projectLocation" => [
                "latitude" => $visit->latitude ? (float) $visit->latitude : null,
                "longitude" => $visit->longitude ? (float) $visit->longitude : null,
            ]
        ];
    }
}
