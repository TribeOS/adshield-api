<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViolationResponse extends Model
{

	protected $table = "trViolationResponses";
	public $timestamps = false;

	public function violation()
	{
		return $this->hasMany('App\Model\Violation', 'violationId');
	}

}