@if($status == 'already_confirmed')
	<p>
		Your account is already active, confirmation isn't required.
	</p>
@elseif($status == 'confirmed')
	<p>
		Your account has been confirmed and activated. You can now log in using your username and password.
	</p>
    <p><a style="background: #28a745;color: #fff;padding: 10px 15px;border-radius: 5px;" href="https://adshield.tribeos.io/">Continue</a></p>
@endif