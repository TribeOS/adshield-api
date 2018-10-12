<?php

namespace App\Http\Controllers\Adshield\Accounts;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Response;
use Hash;
use Validator;
use Illuminate\Http\Request;
use App\Model\User;
use App\Http\Controllers\Adshield\LoginController;


/**
 * handles password updates/changes for the user
 */
class PasswordController extends BaseController {

	public function handle(Request $request, $id=null)
    {
        $userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true); //get USER instead of just the id
        } catch (Exception $e) {
        	die($e->getMessage());
        }

        if ($request->isMethod('post') || $request->isMethod('put'))
        {
        	$data = $request->all();
            return $this->save($id, $data['password']);
        }
    }

    /**
     * create new user/update existing user
     * @return [type] [description]
     */
    private function save($id=null, $data)
    {
	    $validator = Validator::make($data, [
            'password' => 'confirmed|required|max:255',
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
    	return response($user, 200);

    }

    private function remove()
    {

    }

}