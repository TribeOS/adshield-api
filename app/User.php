<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

	const CREATED_AT = 'cretedOn';
    const UPDATED_AT = 'updatedOn';

	protected $table = "users";

}