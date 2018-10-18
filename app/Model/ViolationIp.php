<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViolationIp extends Model
{

	protected $table = "trViolationIps";
	public $timestamps = false;

	public function violation()
	{
		return $this->belongsTo('App\Model\Violation', 'ip');
	}

}