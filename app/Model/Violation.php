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

}