<?php

namespace App\Http\Controllers\Adshield\Accounts;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Response;
use Hash;
use Validator;
use Illuminate\Validation\Rule;
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
        } catch (Exception $e) {
        	die($e->getMessage());
        }

        if ($request->isMethod('get'))
        {
        	$id = $request->get('id', false);
            return $this->getUsers($user->accountId, $id);
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
    		$validator = Validator::make($data, [
                'firstname' => 'required|max:255',
                'lastname' => 'required|max:255',
                'email' => [
                    'email',
                    'max:255',
                    'required',
                    Rule::unique('users')->ignore($id),
                ],
                'username' => [
                    'required',
                    Rule::unique('users')->ignore($id),
                ],
                'password' => 'required|max:100'
            ]);
	    	
	    	if ($validator->fails()) {
                $error = [];
                foreach($validator->errors()->all() as $msg) $error[] = $msg;
                $error = implode("\n", $error);
	    		return response($error, 500)
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
	    	return response($user, 200);
	    }

        if ($data['isReset']) {
            return $this->resetPassword($id, $data);
        }

	    $validator = Validator::make($data, [
    		'firstname' => 'required|max:255',
    		'lastname' => 'required|max:255',
            'email' => [
                'email',
                'max:255',
                'required',
                Rule::unique('users')->ignore($id),
            ],
    		'username' => [
                'required',
                Rule::unique('users')->ignore($id),
            ]
    	]);
        if ($validator->fails()) {
            $error = [];
            foreach($validator->errors()->all() as $msg) $error[] = $msg;
            $error = implode("\n", $error);
            return response($error, 500)
                ->header('Content-Type', 'text/plain');
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
    	return response($user, 200);

    }

    private function resetPassword($id, $data)
    {
        $validator = Validator::make($data, [
            'password' => 'filled|confirmed|required|max:255',
        ]);
        if ($validator->fails()) {
            $error = [];
            foreach($validator->errors()->all() as $msg) $error[] = $msg;
            $error = implode("\n", $error);
            return response($error, 500);
        }

        $user = User::find($id);
        $user->password = Hash::make($data['password']);
        $user->save();
        $user->password = "";
        return response($user, 200);
    }

    private function remove()
    {

    }

}