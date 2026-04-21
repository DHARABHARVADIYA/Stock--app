<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visit;
use Illuminate\Support\Facades\Validator;

class VisitController extends Controller
{

    private function formatVisitResponse($visit)
{
    return [
        'id' => $visit->id,
        'leadGeneratedDate' => $visit->lead_generated_date,


        'business' => $visit->business ? [
            'id' => $visit->business->id,
            'name' => $visit->business->name ?? null,
            'ownerName' => $visit->business->owner_name ?? null,
            'ownerNumber' => $visit->business->owner_number ?? null,
            'gst' => $visit->business->gst ?? null,
            'email' => $visit->business->email ?? null,
            'address' => $visit->business->address ?? null,
        ] : null,

        // (optional) agar alag thi id pan rakhvi hoy
        'businessId' => $visit->business_id,

        'billType' => $visit->bill_type,
        'deliveryAddress' => $visit->delivery_address,
        'deleveryMobileNo' => $visit->delivery_mobile_no,
        'additionlDetails' => $visit->additional_details,
        'birthDaye' => $visit->birthday,
        'anniversaryDate' => $visit->anniversary_date,
        'contractType' => $visit->contract_type,

        'projectLocation' => [
            'latitude' => $visit->project_location_latitude,
            'longitude' => $visit->project_location_longitude,
        ],

        'projectSiteImage' => $visit->project_site_image ?? '',
        'constructionStage' => $visit->construction_stage,
        'BrandUsed' => $visit->brand_used,
        'floorLevel' => $visit->floor_level,
        'projectSize' => $visit->project_size,

        'masonTypes' => is_string($visit->mason_types)
            ? json_decode($visit->mason_types, true)
            : ($visit->mason_types ?? []),

        'plantInterchange' => $visit->plant_interchange,
        'projectType' => $visit->project_type,
        'nextFollowupDate' => $visit->next_followup_date,

        'salesMenId' => $visit->salesmen_id,
        'saleMenName' => $visit->salesmen_name,

        '1hb' => [
            'ownerName' => $visit->one_hb_owner_name,
            'ownerNumber' => $visit->one_hb_owner_number,
        ],

        'rmcPlant' => [
            'ownerName' => $visit->rmc_plant_owner_name,
            'ownerNumber' => $visit->rmc_plant_owner_number,
            'purchaseName' => $visit->rmc_purchase_name,
            'purchaseNumber' => $visit->rmc_purchase_number,
            'labTestingName' => $visit->rmc_lab_name,
            'labTestingNumber' => $visit->rmc_lab_number,
        ],

        'builder' => [
            'ownerName' => $visit->builder_owner_name,
            'ownerNumber' => $visit->builder_owner_number,
            'purchaseName' => $visit->builder_purchase_name,
            'purchaseNumber' => $visit->builder_purchase_number,
            'supervisorName' => $visit->builder_supervisor_name,
            'supervisorNumber' => $visit->builder_supervisor_number,
        ],

        'lwb' => [
            'ownerName' => $visit->lwb_owner_name,
            'ownerNumber' => $visit->lwb_owner_number,
            'purchaseName' => $visit->lwb_purchase_name,
            'purchaseNumber' => $visit->lwb_purchase_number,
        ],
    ];
}
    /* ================= SAVE / UPDATE VISIT ================= */
    public function saveVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'business_id' => 'required|exists:businesses,id',

            'leadGeneratedDate' => 'required|date',
            'billType'          => 'required|integer',
            'contractType'      => 'required|integer',

            'project_site_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation Error',
                'result'  => $validator->errors()
            ], 422); // <- Set HTTP status code explicitly
        }


        /* IMAGE UPLOAD */
        $imagePath = null;
        if ($request->hasFile('project_site_image')) {
            $imagePath = $request->file('project_site_image')->store('visits', 'public');
        }

        /* VISIT DATA */
        $visitData = [
            'business_id' => $request->business_id,
            'lead_generated_date' => $request->leadGeneratedDate,
            'bill_type' => $request->billType,
            'contract_type' => $request->contractType,

            'delivery_address'   => $request->deliveryAddress ?? null,
            'delivery_mobile_no' => $request->deleveryMobileNo ?? null,
            'additional_details' => $request->additionlDetails ?? null,
            'birthday'           => $request->birthDaye ?? null,
            'anniversary_date'   => $request->anniversaryDate ?? null,

            'project_location_latitude'  => $request->projectLocation['latitude'] ?? null,
            'project_location_longitude' => $request->projectLocation['longitude'] ?? null,

            'construction_stage' => $request->constructionStage ?? 0,
            'brand_used'         => $request->BrandUsed ?? null,
            'floor_level'        => $request->floorLevel ?? null,
            'project_size'       => $request->projectSize ?? null,
            'mason_types' => $request->masonTypes ?? [],


            'plant_interchange'  => $request->plantInterchange ?? null,
            'project_type'       => $request->projectType ?? null,
            'next_followup_date' => $request->nextFollowupDate ?? null,

            'salesmen_id'   => $request->salesMenId ?? null,
            'salesmen_name' => $request->saleMenName ?? null,

            'project_site_image' => $imagePath,

            // 1HB
            'one_hb_owner_name'   => $request['1hb']['ownerName'] ?? null,
            'one_hb_owner_number' => $request['1hb']['ownerNumber'] ?? null,

            // RMC
            'rmc_plant_owner_name'   => $request['rmcPlant']['ownerName'] ?? null,
            'rmc_plant_owner_number' => $request['rmcPlant']['ownerNumber'] ?? null,
            'rmc_purchase_name'      => $request['rmcPlant']['purchaseName'] ?? null,
            'rmc_purchase_number'    => $request['rmcPlant']['purchaseNumber'] ?? null,
            'rmc_lab_name'           => $request['rmcPlant']['labTestingName'] ?? null,
            'rmc_lab_number'         => $request['rmcPlant']['labTestingNumber'] ?? null,

            // Builder
            'builder_owner_name'      => $request['builder']['ownerName'] ?? null,
            'builder_owner_number'    => $request['builder']['ownerNumber'] ?? null,
            'builder_purchase_name'   => $request['builder']['purchaseName'] ?? null,
            'builder_purchase_number' => $request['builder']['purchaseNumber'] ?? null,
            'builder_supervisor_name' => $request['builder']['superwiserName'] ?? null,
            'builder_supervisor_number' => $request['builder']['superwiserNumber'] ?? null,

            // LWB
            'lwb_owner_name'      => $request['LWB_TA_PRE']['ownerName'] ?? null,
            'lwb_owner_number'    => $request['LWB_TA_PRE']['ownerNumber'] ?? null,
            'lwb_purchase_name'   => $request['LWB_TA_PRE']['purchaseName'] ?? null,
            'lwb_purchase_number' => $request['LWB_TA_PRE']['purchaseNumber'] ?? null,
        ];

        /* CREATE / UPDATE */
        if ($request->id == 0) {
            $visit = Visit::create($visitData);
            $message = 'Visit added successfully';
        } else {
            $visit = Visit::find($request->id);

            if (!$visit) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Visit not found',
                    'result' => null
                ], 404); //
            }


            if (!$imagePath) {
                unset($visitData['project_site_image']);
            }

            $visit->update($visitData);
            $message = 'Visit updated successfully';
        }

        /* ===== PROPER RESPONSE FORMAT ===== */
        $visit = Visit::with('business')->find($visit->id);

        return response()->json([
            'status' => 200,
            'message' => $message,
            'result' => $this->formatVisitResponse($visit)
        ]);
    }


    /* ================= DELETE VISIT ================= */
    public function deleteVisit(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $visit = Visit::find($request->id);
        if (!$visit) {
            return response()->json([
                'status' => 404,
                'message' => 'Visit not found',
                'result' => null
            ]);
        }

        $visit->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Visit deleted successfully',
            'result' => null
        ]);
    }

    /* ================= GET ALL VISITS ================= */
    public function getAllVisit()
    {
        $visits = Visit::with('business')
            ->orderBy('id', 'desc')
            ->get();

        $result = $visits->map(
            fn($visit) =>
            $this->formatVisitResponse($visit)
        );

        return response()->json([
            'status' => 200,
            'message' => 'Visits fetched successfully',
            'result' => $result
        ]);
    }



    public function getVisitByBusiness(Request $request)
    {
        try {
            /* ================= VALIDATION ================= */
            $validator = Validator::make($request->all(), [
                'business_id' => 'required|integer|exists:businesses,id',
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status'  => 422,
                    'message' => 'Validation Error',
                    'result'  => $validator->errors()
                ], 422);
            }

            /* ================= FETCH VISITS ================= */
            $visits = Visit::with('business')
                ->where('business_id', $request->business_id)
                ->orderBy('id', 'desc')
                ->get();

            if ($visits->isEmpty()) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No visits found for this business',
                    'result'  => []
                ], 404);
            }

            /* ================= RESPONSE FORMAT ================= */
            $result = $visits->map(
                fn($visit) => $this->formatVisitResponse($visit)
            );


            return response()->json([
                'status'  => 200,
                'message' => 'Visits fetched successfully',
                'result'  => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
