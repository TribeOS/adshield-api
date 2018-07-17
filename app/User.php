<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

	const CREATED_AT = 'createdOn';
    const UPDATED_AT = 'updatedOn';

	protected $table = "users";

	public function config()
	{
		return $this->hasOne('App\UserConfig', 'userId');
	}

}