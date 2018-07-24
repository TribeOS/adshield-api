<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserConfig extends Model
{

	const CREATED_AT = 'createdOn';
    const UPDATED_AT = 'updatedOn';

	protected $table = "asUserConfig";

	public function user()
	{
		return $this->belongsTo('App\User', 'userId');
	}

	public function getConfigJson($configName)
	{
		return isset(json_decode($this->config, 1)[$configName]) ? json_decode($this->config, 1)[$configName] : null;
	}

}