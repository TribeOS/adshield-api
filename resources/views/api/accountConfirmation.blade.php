@if($status == 'already_confirmed')
	<p>
		Your account is already active, confirmation isn't required.
	</p>
@elseif($status == 'confirmed')
	<p>
		Your account has been confirmed and activated. You can now log in using your username and password.
	</p>
@endif