<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\State;
use App\District;
use App\Taluka;

class MachineMou extends \Jenssegers\Mongodb\Eloquent\Model
{
	use AuditFields;

    protected $table = 'machine_mou';

    protected $fillable = [
        'mou_id',
        'state',
        'district',
        'taluka',
        'machine_type',
        'machine_code',
        'provider_name',
        'provider_contact_number',
        'ownership_type',
        'provider_trade_name',
        'turnover_less_than_20',
        'gst_number',
        'pan_number',
        'bank_name',
        'branch',
        'ifsc_code',
        'bank_account_number',
        'account_holders_name',
        'account_type',
        'date_of_signing_contract',
        'mou_cancellation',
        'rate1_from',
        'rate1_to',
        'rate1_value',
        'rate2_from',
        'rate2_to',
        'rate2_value',
        'rate3_from',
        'rate3_to',
        'rate3_value',

        'form_id',
        'userName'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function taluka()
    {
        return $this->belongsTo(Taluka::class);
    }
	
	public function machineData()
    {
        return $this->hasMany('App\Machine', '_id', 'provider_information.machine_id');
    }
	 
}
