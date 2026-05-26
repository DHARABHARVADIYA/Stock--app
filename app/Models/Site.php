<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [

        
        'order_id',
        'lead_generated_date',


        'name',
        'owner_name',
        'owner_number',
        'gst',
        'email',
        'address',


        'manager_name',
        'manager_mobile_no',


        'bill_type',


        'delivery_address',
        'delivery_mobile_no',


        'additional_details',

        'birthday',
        'anniversary_date',

        'contract_type',


        'project_location_latitude',
        'project_location_longitude',

        
        'project_site_image',

        'construction_stage',

        'brand_used',
        'floor_level',
        'project_size',
        
        

        'mason_types',
        
        'required_balance',
        'project_duration',

        
        'one_hb_owner_name',
        'one_hb_owner_number',


        'rmc_plant_owner_name',
        'rmc_plant_owner_number',
        'rmc_purchase_name',
        'rmc_purchase_number',
        'rmc_lab_testing_name',
        'rmc_lab_testing_number',


        'builder_owner_name',
        'builder_owner_number',
        'builder_purchase_name',
        'builder_purchase_number',
        'builder_supervisor_name',
        'builder_supervisor_number',


        'lwb_owner_name',
        'lwb_owner_number',
        'lwb_purchase_name',
        'lwb_purchase_number',

        
        'architect_type',
        'plant_interchange',
        'project_type',
        'next_followup_date',
        'salesman_id',
        'salesman_name',
    ];


    protected $casts = [

        'mason_types' => 'array',

        'next_followup_date' => 'date',

        'plant_interchange' => 'boolean',
    ];
    
      public function orders()
    {
        return $this->hasMany(Order::class, 'site_visit_id');
    }

}
