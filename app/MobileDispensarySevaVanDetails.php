<?php 
namespace App;
 

use Illuminate\Database\Eloquent\Model;


class MobileDispensarySevaVanDetails extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'mobileDispensarySeva_vanDetails';



    public function vanCity() 
	{
		return $this->hasOne('App\MobileDispensarySevaVanCity','_id','city_id');	
	}
	
	public function vanDepot() 
	{
		return $this->hasOne('App\MobileDispensarySevaVanDepot','_id','depot_id');	
	}
    
}