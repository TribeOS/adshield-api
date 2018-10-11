<?php

namespace App\Http\Controllers\Adshield\Accounts;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Response;
use Hash;
use Illuminate\Http\Request;
use App\Model\User;
use App\Model\UserPermission;
use App\Http\Controllers\Adshield\LoginController;


class UserController extends BaseController {

	public function handle(Request $request, $id=null)
    {
        $userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true); //get USER instead of just the id
        } catch (Exception $e) {}

        if ($request->isMethod('get'))
        {
        	$filter = $request->get('filter', false);
            return $this->getUsers($user->accountId, $filter['userId']);
        }
        else if ($request->isMethod('post') || $request->isMethod('put'))
        {
        	$data = $request->all();
            return $this->save($id, $data['user'], $user->accountId);
        }
        else if ($request->isMethod('delete'))
        {
            return $this->remove();
        }
    }


    /**
     * get all users belonging to this account
     * @param  [type] $accountId [description]
     * @return [type]            [description]
     */
    private function getUsers($accountId, $userId=false)
    {
    	if (!empty($userId)) {
    		$user = DB::table("users")
    			->leftJoin("usersPermission", "usersPermission.userId", "=", "users.id")
    			->select(DB::raw("users.id, accountId, firstname, lastname, email, username, usersPermission.permission"))
	    		->where("users.id", $userId)
	    		->first();
	    	if (!empty($user)) return Response::json(['id' => $user->id, 'data' => $user]);
	    	return response("User not found.", 500);
    	} else {
	    	$users = DB::table("users")
	    		->leftJoin("usersPermission", "usersPermission.userId", "=", "users.id")
	    		->select(DB::raw("users.id, accountId, firstname, lastname, email, username, usersPermission.permission"))
	    		->where("users.accountId", $accountId)
	    		->get();
    		return $users;
    	}
    }

    /**
     * create new user/update existing user
     * @return [type] [description]
     */
    private function save($id=null, $data, $accountId)
    {
    	$email = $data['email'];
    	if (empty($id))
    	{
	    	$user = User::where("email", $email)->first();
	    	if (!empty($user)) {
	    		return response("The email '$email' already exists. Please register a different email address.", 500)
	                ->header('Content-Type', 'text/plain');
	    	}
	    	//create user
	    	$user = new User();
	    	$user->firstname = $data['firstname'];
	    	$user->lastname = $data['lastname'];
	    	$user->email = $data['email'];
	    	$user->username = $data['username'];
	    	$user->password = Hash::make($data['password']);
	    	$user->accountId = $accountId;
	    	$user->save();
	    	$usersPermission = new UserPermission();
	    	$usersPermission->userId = $user->id;
	    	$usersPermission->permission = 1;
	    	$usersPermission->save();
	    	$user->permission = $usersPermission->permission;
	    	$user->password = "";
	    	return $user;
	    }

	    $user = User::find($id);
	    $user->firstname = $data['firstname'];
    	$user->lastname = $data['lastname'];
    	$user->username = $data['username'];
    	$user->email = $data['email'];
    	$user->save();
    	$usersPermission = UserPermission::where('userId', $user->id)->first();
    	$usersPermission->permission = $data['permission'];
    	$usersPermission->save();
    	$user->permissions = $usersPermission->permission;
    	$user->password = "";
    	return $user;

    }

    private function remove()
    {

    }

}