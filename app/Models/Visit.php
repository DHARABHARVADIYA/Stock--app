<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $table = 'visits';

    protected $fillable = [
        'lead_generated_date',  'business_id',
        'manager_name', 'manager_mobile_no', 'bill_type', 'delivery_address', 'delivery_mobile_no',
        'additional_details',  'birthday', 'anniversary_date', 'contract_type',
        'project_location_latitude', 'project_location_longitude', 'project_site_image',
        'construction_stage', 'brand_used', 'floor_level', 'project_size', 'mason_types',
        'one_hb_owner_name','one_hb_owner_number',
        'rmc_plant_owner_name','rmc_plant_owner_number','rmc_purchase_name','rmc_purchase_number',
        'rmc_lab_name','rmc_lab_number',
        'builder_owner_name','builder_owner_number','builder_purchase_name','builder_purchase_number',
        'builder_supervisor_name','builder_supervisor_number',
        'architect_type','lwb_owner_name','lwb_owner_number','lwb_purchase_name','lwb_purchase_number',
        'plant_interchange','project_type','next_followup_date','salesmen_id','salesmen_name'
    ];

    protected $casts = [
        'mason_types' => 'array',
    ];

    public function business()
{
    return $this->belongsTo(Business::class);
}

public function site()
{
    return $this->belongsTo(Site::class, 'site_id');
}

}
