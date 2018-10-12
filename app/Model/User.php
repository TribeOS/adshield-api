<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    public $timestamps = false;

	protected $table = "users";

	public function config()
	{
		return $this->hasOne('App\Model\UserConfig', 'userId');
	}

	public function account()
	{
		return $this->belongsTo('App\Model\Account', 'accountId');
	}

	public function permission()
	{
		return $this->hasOne('App\Model\UserPermission', 'userId');
	}

}