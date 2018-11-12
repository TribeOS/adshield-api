<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ViolationSession extends Model
{

	protected $table = "trViolationSession";
	public $timestamps = false;

	public function myIp()
	{
		return $this->hasMany('App\Model\ViolationIp', 'ip');
	}

}