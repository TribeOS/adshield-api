<h2>Notification from AdShield</h2>

@if($type == 'settings')
	<h3>
		Violation Detected!
	</h3>
	<table>
		<tr>
			<td>Website</td>
			<td>{{ $violationo->website->domain }}</td>
		</tr>
		<tr>
			<td>Violation</td>
			<td>{{ $violation->violation }}</td>
		</tr>
		<tr>
			<td>Date/Time</td>
			<td>{{ $violation->createdOn }}</td>
		</tr>
		<tr>
			<td>Url</td>
			<td>{{ $violation->info->fullUrl }}</td>
		</tr>
		<tr>
			<td>User Agent</td>
			<td>{{ $violation->info->userAgent }}</td>
		</tr>
		<tr>
			<td>Country</td>
			<td>{{ $violation->info->country }}</td>
		</tr>
		<tr>
			<td>City</td>
			<td>{{ $violation->info->city }}</td>
		</tr>
		<tr>
			<td>IP</td>
			<td>{{ $violation->myIp->ipStr }}</td>
		</tr>
	</table>
@elseif($type == 'violations')
	<h3>
		Settings Updated!
	</h3>
	<table>
		<tr>
			<td></td>
		</tr>
	</table>
@endif