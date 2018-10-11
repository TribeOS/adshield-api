<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserWebsite extends Model
{

	public $timestamps = false;

	protected $table = "userWebsites";

	public function account()
	{
		return $this->belongsTo('App\Model\Account', 'accountId');
	}

}