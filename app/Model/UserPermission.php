<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;


class UserPermission extends Model
{

	public $timestamps = false;
	public $primaryKey = "userId";

	protected $table = "usersPermission";

	public function account()
	{
		return $this->belongsTo('App\Model\Account', 'accountId');
	}

}