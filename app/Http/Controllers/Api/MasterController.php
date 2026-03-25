<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GstMaster;
use App\Models\PaymentMode;
use App\Models\BillType;
use App\Models\ContractType;
use App\Models\ConstructionStage;
use App\Models\ArchitectType;
use App\Models\MasonType;
use App\Models\PlantInterchange;
use App\Models\ProjectType;
use App\Models\TransportType;

class MasterController extends Controller
{
    public function getMaster()
    {
        return response()->json([
            'status'  => 200,
            'message' => 'Master data fetched successfully',
            'result'  => [
                'GST' => GstMaster::select('id','name')->get(),
                'PaymentMode' => PaymentMode::select('id','name','image')->get(),
                'BillType' => BillType::select('id','name')->get(),
                'ContractType' => ContractType::select('id','name')->get(),
                'ConstructionStage' => ConstructionStage::select('id','name')->get(),
                'ArchitectType' => ArchitectType::select('id','name')->get(),
                'MasonType' => MasonType::select('id','name')->get(),
                'PlantInterchange' => PlantInterchange::select('id','name')->get(),
                'ProjectType' => ProjectType::select('id','name')->get(),
                'TransportType' => TransportType::select('id','name')->get(),
            ]
        ], 200);
    }
}
