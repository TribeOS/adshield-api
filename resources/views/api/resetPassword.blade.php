<p>
	We've sent you this email because you (or someone else) requested to reset your password on AdShield.
</p>
<p>
	To proceed, please click on the "Reset My Password" button below and follow the next instructions.
</p>
<p>
	<a style="background: #28a745;color: #fff;padding: 10px 15px;border-radius: 5px;" href="{{ route('ResetPassword', ['hash' => $user->resetHash]) }}">Reset My Password</a>
</p>