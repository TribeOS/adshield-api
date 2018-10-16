<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViolationInfo extends Model
{

	protected $table = "trViolationInfo";
	public $timestamps = false;

	public function info()
	{
		return $this->hasMany('App\Model\Violation', 'violationInfo');
	}

}