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
use App\Model\Account;
use App\Http\Controllers\Adshield\LoginController;

use Illuminate\Support\Facades\Mail;
use App\Mail\AccountCreated;



/**
 * handles Account creation/signup
 */
class AccountController extends BaseController {

	const AC_ACTIVE = 'active';
	const AC_INACTIVE = 'inactive';
	const AC_UNCONFIRMED = 'unconfirmed';
	
	public function handle(Request $request, $id=null)
	{
		if ($request->isMethod('post'))
		{
			$data = $request->all();
			return $this->SignUp($data['account']);
		}
	}


	/**
	 * process the user info and creates a new account record
	 * this will also create a new user under the new account
	 */
	private function SignUp($data)
	{
		$validator = Validator::make($data, [
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'email' => [
                'email',
                'max:255',
                'required',
                Rule::unique('account')
            ],
            'username' => [
                'required',
                Rule::unique('users'),
            ],
            'password' => 'required|max:100'
        ]);

        if ($validator->fails()) {
            $error = [];
            foreach($validator->errors()->all() as $msg) $error[] = trim($msg);
            $error = implode("\n", $error);
    		return response($error, 500);
    	}

    	try {
    		$this->Create($data);
    	} catch (\Exception $e) {
    		return response([
    			'errors' => [
    				'detail' => $e->getMessage()
    			]
			], 
			500);
    	}

		return response(['account' => $data, 'id' => 1], 200);
	}


	/**
	 * creates an account record
	 */
	private function Create($data)
	{
		$config = [
			'account' => [
				'company' => $data['company'],
				'address' => ''
			],
		];

		$hash = md5(time().'adshield');
		$account = new Account();
		$account->createdOn = gmdate("Y-m-d H:i:s");
		$account->status = self::AC_UNCONFIRMED;
		$account->config = json_encode($config);
		$account->email = $data['email'];
		$account->confirmation = $hash;
		$account->save();
		$accountId = $account->id;

		//create first user
		$user = new User();
		$user->firstname = $data['firstname'];
    	$user->lastname = $data['lastname'];
    	$user->email = $data['email'];
    	$user->username = $data['username'];
    	$user->password = Hash::make($data['password']);
    	$user->accountId = $accountId;
    	$user->save();

    	//create user permission
    	$usersPermission = new UserPermission();
    	$usersPermission->userId = $user->id;
    	$usersPermission->permission = 1;
    	$usersPermission->save();
    	$user->permission = $usersPermission->permission;
    	$user->password = "";


    	//TODO
    	//send email
    	Mail::to($account->email)->send(new AccountCreated($account));
	}


	/**
	 * handles confirmation of user email
	 */
	public function Confirm($code)
	{
		//get code
		$account = Account::where("confirmation", $code)->first();
		if (empty($account)) {
			return response("Invalid confirmation code. If you've opened this from your email, its possible that your account has already been removed already.", 500);
		}
		//check against "unconfirmed" Accounts
		if ($account->status == self::AC_ACTIVE) {
			return response("Your account is already active, confirmation isn't required.", 200);
		}
		//set to "active" account
		$account->status = self::AC_ACTIVE;
		$account->save();
		//inform frontend of confirmation
		return response("Your account has been confirmed and activated. You can now log in using your username and password.", 200);
	}


}