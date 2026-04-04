<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Site;
use App\Http\Resources\SiteResource;
use Validator;

class SiteController extends Controller
{
    /* ========== COMMON RESPONSE ========== */
    protected function sendResponse($success, $message, $data = null, $code = 200)
    {
        return response()->json([
            'status' => $success,
            'message' => $message,
            'result' => $data
        ], $code);
    }

    // ================= Get All =================
    public function index()
    {
        $user = auth('api')->user();

        $query = Site::query();

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $sites = $query->latest()->get();

        return $this->sendResponse(true, 'Site list fetched', SiteResource::collection($sites));
    }

    // ================= Get By ID =================
    public function getById(Request $request)
    {
        $user = auth('api')->user();

        $query = Site::where('id', $request->id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $site = $query->first();

        if (!$site) {
            return $this->sendResponse(false, 'Site not found', null, 404);
        }

        return $this->sendResponse(true, 'Site fetched successfully', new SiteResource($site));
    }

    // ================= Save (Add / Update) =================
    public function save(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [

            'lead_generated_date' => 'required|date',
            'name' => 'required|string',
            'owner_name' => 'required|string',
            'owner_number' => 'required|string',

            'bill_type' => 'required|integer',
            'contract_type' => 'required|integer',
            'construction_stage' => 'required|integer',

            'mason_types' => 'nullable|array',
            'mason_types.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(false, 'Validation error', $validator->errors(), 422);
        }

        // Update / Insert
        if ($request->id) {

            $query = Site::where('id', $request->id);

            if ($user->role == 'admin') {
                $query->where('business_code', $user->business_code);
            } else if ($user->role == 'sales') {
                $query->where('created_by', $user->id);
            }

            $site = $query->first();

            if (!$site) {
                return $this->sendResponse(false, 'Site not found', null, 404);
            }
        } else {
            $site = new Site();

            $site->created_by = $user->id;
            $site->business_code = $user->business_code;
        }

        // Image Upload
        if ($request->hasFile('project_site_image')) {
            $image = $request->file('project_site_image');
            $name = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('site_images', $name, 'public');
            $site->project_site_image = $path;
        }

        if ($request->has('masonTypes') && is_string($request->masonTypes)) {
            $request->merge([
                'masonTypes' => json_decode($request->masonTypes, true)
            ]);
        }

        // Save Data
        $site->fill([

            'order_id' => $request->order_id ?? 0,
            'lead_generated_date' => $request->lead_generated_date,

            'name' => $request->name,
            'owner_name' => $request->owner_name,
            'owner_number' => $request->owner_number,
            'gst' => $request->gst,
            'email' => $request->email,
            'address' => $request->address,

            'manager_name' => $request->manager_name,
            'manager_mobile_no' => $request->manager_mobile_no,

            'bill_type' => $request->bill_type,

            'delivery_address' => $request->delivery_address,
            'delivery_mobile_no' => $request->delivery_mobile_no,

            'additional_details' => $request->additional_details,

            'birthday' => $request->birthday,
            'anniversary_date' => $request->anniversary_date,

            'contract_type' => $request->contract_type,

            'project_location_latitude' => $request->project_location_latitude,
            'project_location_longitude' => $request->project_location_longitude,

            'construction_stage' => $request->construction_stage,

            'brand_used' => $request->brand_used,
            'floor_level' => $request->floor_level,
            'project_size' => $request->project_size,

            'mason_types' => $request->masonTypes ?? [],

            'one_hb_owner_name' => $request->one_hb_owner_name,
            'one_hb_owner_number' => $request->one_hb_owner_number,

            'rmc_plant_owner_name' => $request->rmc_plant_owner_name,
            'rmc_plant_owner_number' => $request->rmc_plant_owner_number,

            'rmc_purchase_name' => $request->rmcPlant['purchaseName'] ?? null,
            'rmc_purchase_number' => $request->rmcPlant['purchaseNumber'] ?? null,

            'rmc_lab_testing_name' => $request->rmcPlant['labTestingName'] ?? null,
            'rmc_lab_testing_number' => $request->rmcPlant['labTestingNumber'] ?? null,

            'builder_owner_name' => $request->builder['ownerName'] ?? null,
            'builder_owner_number' => $request->builder['ownerNumber'] ?? null,

            'builder_purchase_name' => $request->builder['purchaseName'] ?? null,
            'builder_purchase_number' => $request->builder['purchaseNumber'] ?? null,

            'builder_supervisor_name' => $request->builder['supervisorName'] ?? null,
            'builder_supervisor_number' => $request->builder['supervisorNumber'] ?? null,

            'lwb_owner_name' => $request->lwb['ownerName'] ?? null,
            'lwb_owner_number' => $request->lwb['ownerNumber'] ?? null,

            'lwb_purchase_name' => $request->lwb['purchaseName'] ?? null,
            'lwb_purchase_number' => $request->lwb['purchaseNumber'] ?? null,

            'architect_type' => $request->architectType,
            'plant_interchange' => (int) $request->plantInterchange,
            'project_type' => $request->projectType,

            'next_followup_date' => $request->nextFollowupDate,

            'salesman_id' => $request->salesMenId,
            'salesman_name' => $request->saleMenName,
        ]);

        $site->save();

        return $this->sendResponse(true, 'Site saved successfully', new SiteResource($site));
    }

    // ================= Delete =================
    public function delete(Request $request)
    {
        $user = auth('api')->user();

        $query = Site::where('id', $request->id);

        if ($user->role == 'admin') {
            $query->where('business_code', $user->business_code);
        } else if ($user->role == 'sales') {
            $query->where('created_by', $user->id);
        }

        $site = $query->first();

        if (!$site) {
            return $this->sendResponse(false, 'Site not found', null, 404);
        }

        $site->delete();

        return $this->sendResponse(true, 'Site deleted successfully', null);
    }
}
