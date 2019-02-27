<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MachineMaster extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'machine_masters';

    protected $fillable = [
        
        'machine_code','machine_make','machine_model','rto_number','chassis_number','taluka_id','provider_name',
        'providers_contact', 'ownership_type','provider_trade_name','turnover_less_than_20',
        'gst_number','pan_number', 'bank_details','excavation_capacity_per_hour',
        'diesel_tank_capacity_in_litres','mou_id', 'date_of_signing_contract','mou_cancellation',
        'district_id','created_by','state_id'
        // 'pin','name','type','machine_code','district','taluka',
    ];

}
