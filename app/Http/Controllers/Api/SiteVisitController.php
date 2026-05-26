<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteVisit;
use App\Models\User;
use Validator;
use Carbon\Carbon;
use App\Models\Site;


class SiteVisitController extends Controller
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

    // ================= Save / Update =================
 public function save(Request $request)
{
    $user = auth('api')->user();

    $validator = Validator::make($request->all(), [

        'site_id' => 'required|exists:sites,id',
        'sales_man_id' => 'nullable|exists:users,id',
        'visit_date' => 'required|date_format:Y-m-d H:i:s',
       'next_visit_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:visit_date',
        'order_id' => 'nullable|exists:orders,id',
        'order_amount' => 'nullable|numeric',
        'note' => 'nullable|string',
        'visit_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',

    ]);

    if ($validator->fails()) {
        return $this->sendResponse(
            422,
            $validator->errors()->all(),
            (object)[],
            422
        );
    }

    /*
    ==========================================
    LOGIN TOKEN mathi salesman data levanu
    ==========================================
    */
    $salesManId   = $user->id;
    $salesManName = $user->name;

    if ($request->id) {

        $query = SiteVisit::where('id', $request->id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $visit = $query->first();

        if (!$visit) {
            return $this->sendResponse(
                404,
                ['Visit not found'],
                (object)[],
                404
            );
        }

    } else {

        $visit = new SiteVisit();
        $visit->created_by = $user->id;
        $visit->business_code = $user->business_code;
    }

    $visit->fill([
        'site_id' => $request->site_id,

        // site creator nai, login user nu data save thase
        'sales_man_id' => $salesManId,
        'sales_man_name' => $salesManName,

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

/*
|--------------------------------------------------------------------------
| Update Site next_followup_date
|--------------------------------------------------------------------------
*/
Site::where('id', $request->site_id)
    ->update([
        'next_followup_date' => $request->next_visit_date
    ]);

return $this->sendResponse(
    200,
    ['Site visit saved successfully'],
    $this->formatVisit($visit)
);

  
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

        return $this->sendResponse(
            200,
            ['Site visit list fetched'],
            $visits->map(function ($visit) {
                return $this->formatVisit($visit);
            })
        );
    }

    // ================= Get By Site =================
public function getBySite(Request $request)
{
    $validator = Validator::make($request->all(), [
        'site_id' => 'required|numeric'
    ]);

    if ($validator->fails()) {
        return $this->sendResponse(
            422,
            $validator->errors()->all(),
            [],
            422
        );
    }

    $visits = SiteVisit::where('site_id', $request->site_id)
        ->latest()
        ->get();

    return $this->sendResponse(
        200,
        ['Site visit fetched successfully'],
        $visits->map(function ($visit) {
            return $this->formatVisit($visit);
        })
    );
}

    // ================= Delete =================
    public function delete(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:site_visits,id'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                (object)[],
                422
            );
        }

        $query = SiteVisit::where('id', $request->id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $visit = $query->first();

        if (!$visit) {
            return $this->sendResponse(
                404,
                ['Visit not found'],
                (object)[],
                404
            );
        }

        if ($visit->visit_image && file_exists(public_path($visit->visit_image))) {
            unlink(public_path($visit->visit_image));
        }

        $visit->delete();

        return $this->sendResponse(
            200,
            ['Visit deleted successfully'],
            (object)[]
        );
    }

    // ================= Format Response =================
    private function formatVisit($visit)
    {
        return [
            "id" => (int) $visit->id,
            "siteId" => (int) $visit->site_id,
            "siteName" => optional($visit->site)->name,
            "salesManId" => $visit->sales_man_id ? (int) $visit->sales_man_id : null,
            "salesManName" => $visit->sales_man_name,
            "date" => Carbon::parse($visit->visit_date)->format('Y-m-d H:i:s'),
           "nextVisitDate" => Carbon::parse($visit->next_visit_date)
    ->format('Y-m-d H:i:s'),
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