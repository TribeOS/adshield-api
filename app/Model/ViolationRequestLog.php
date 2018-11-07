<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViolationRequestLog extends Model
{

	protected $table = "trViolationLog";
	public $timestamps = false;

	public function myIp()
	{
		return $this->hasMany('App\Model\ViolationIp', 'ip');
	}

}