<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {

          $lastOrder = \App\Models\Order::where('site_visit_id', $this->id)
                        ->latest('created_at')
                        ->first();


        if ($lastOrder) {
            $date = $lastOrder->created_at;
        } else {
            $date = $this->created_at;
        }

        $days = Carbon::parse($date)->diffInDays(now());
        $isExpiry = $days > 15 ? true : false;

        return [

            "id" => $this->id,

            "leadGeneratedDate" => $this->lead_generated_date,

            "name" => $this->name,
            "ownerName" => $this->owner_name,
            "ownerNumber" => $this->owner_number,
            "gst" => $this->gst,
            "email" => $this->email,
            "address" => $this->address,

            "managerName" => $this->manager_name,
            "managerMobileNo" => $this->manager_mobile_no,

            "billType" => (int) $this->bill_type,

            "deliveryAddress" => $this->delivery_address,
            "deleveryMobileNo" => $this->delivery_mobile_no,

            "additionlDetails" => $this->additional_details,

            "birthDaye" => $this->birthday,
            "anniversaryDate" => $this->anniversary_date,

            "contractType" => (int) $this->contract_type,

            "projectLocation" => [
                "latitude" => (float) $this->project_location_latitude,
                "longitude" => (float) $this->project_location_longitude,
            ],

            "projectSiteImage" => $this->project_site_image,

            "constructionStage" => (int) $this->construction_stage,

            "BrandUsed" => $this->brand_used,
            "floorLevel" => $this->floor_level,
            "projectSize" => $this->project_size,

            "requiredBalance" => $this->required_balance,
            "projectDuration" => $this->project_duration,

            "masonTypes" => $this->mason_types ?? [],


            "1hb" => [
                "ownerName" => $this->one_hb_owner_name,
                "ownerNumber" => $this->one_hb_owner_number,
            ],

            "rmcPlant" => [
                "ownerName" => $this->rmc_plant_owner_name,
                "ownerNumber" => $this->rmc_plant_owner_number,
                "purchaseName" => $this->rmc_purchase_name,
                "purchaseNumber" => $this->rmc_purchase_number,
                "labTestingName" => $this->rmc_lab_testing_name,
                "labTestingNumber" => $this->rmc_lab_testing_number,
            ],

            "builder" => [
                "ownerName" => $this->builder_owner_name,
                "ownerNumber" => $this->builder_owner_number,
                "purchaseName" => $this->builder_purchase_name,
                "purchaseNumber" => $this->builder_purchase_number,
                "supervisorName" => $this->builder_supervisor_name,
                "supervisorNumber" => $this->builder_supervisor_number,
            ],

            "lwb" => [
                "ownerName" => $this->lwb_owner_name,
                "ownerNumber" => $this->lwb_owner_number,
                "purchaseName" => $this->lwb_purchase_name,
                "purchaseNumber" => $this->lwb_purchase_number,
            ],

            "architectType" => (int) $this->architect_type,
            "plantInterchange" => (int) $this->plant_interchange,

            "projectType" => $this->project_type,

            "nextFollowupDate" => $this->next_followup_date,

            "salesMenId" => (int) $this->salesman_id,
            "saleMenName" => $this->salesman_name,

             "isExpiry" => $isExpiry,
        ];
    }
}
