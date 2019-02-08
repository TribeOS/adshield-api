<h2>Notification from AdShield</h2>

@if($type == 'violations')
	<h3>
		Violation Detected!
	</h3>
	<table>
		<tr>
			<td>Website :</td>
			<td>{{ $data->website->domain }}</td>
		</tr>
		<tr>
			<td>Violation :</td>
			<td>{{ $data->violation }}</td>
		</tr>
		<tr>
			<td>Date/Time :</td>
			<td>{{ $data->createdOn }}</td>
		</tr>
		<tr>
			<td>Url :</td>
			<td>{{ $data->info->fullUrl }}</td>
		</tr>
		<tr>
			<td>User Agent :</td>
			<td>{{ $data->info->userAgent }}</td>
		</tr>
		<tr>
			<td>Country :</td>
			<td>{{ $data->info->country }}</td>
		</tr>
		<tr>
			<td>City :</td>
			<td>{{ $data->info->city }}</td>
		</tr>
		<tr>
			<td>IP :</td>
			<td>{{ $data->myIp->ipStr }}</td>
		</tr>
	</table>
@elseif($type == 'settings')
	<h3>
		Settings in AdShield was updated!
	</h3>
	<table>
		<tr>
			<td>User :</td>
			<td>{{ $data->user }}</td>
		</tr>
		<tr>
			<td>Updated On :</td>
			<td>{{ $data->updatedOn }}</td>
		</tr>
		<tr>
			<td>Setting :</td>
			<td>{{ $data->setting }}</td>
		</tr>
		<tr>
			<td>Description :</td>
			<td>{{ $data->description }}</td>
		</tr>
		
	</table>
@endif