<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

	protected $timestamps = false;

	protected $table = "account";

	public function users()
	{
		return $this->hasMany('App\Model\User', 'accountId');
	}

	public function websites()
	{
		return $this->hasMany('App\Model\UserWebsite', 'accountId');
	}

}