<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{

	protected $table = "trViolations";
	public $timestamps = false;

	public function info()
	{
		return $this->belongsTo('App\Model\ViolationInfo', 'violationInfo');
	}

	public function myIp()
	{
		return $this->belongsTo('App\Model\ViolationIp', 'ip', 'id');
	}

	public function website()
	{
		return $this->belongsTo('App\Model\UserWebsite', 'userKey', 'userKey');
	}

}