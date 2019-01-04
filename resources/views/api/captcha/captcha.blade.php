<html>
<head>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 

</head>
<body>

	<form method='post' action="{{ route('CaptchaHandler') }}">
		@csrf
		<img src="captcha" />
		<input type="text" id="captcha" name="captcha" />
		<button>Submit</button>
	</form>
<!-- 
	<script>
		$(function() {
			$.ajaxSetup({
			  headers: {
			    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			  }
			});

			$("form").submit(function() {
				arg = { 
					captcha : $("#captcha").val(),
					_token : "{{ csrf_token() }}" 
				};
				$.post("{{ route('CaptchaHandler') }}", arg, function(response) {
					console.log(response);
				});
				return false;
			});
		});
	</script> -->

</body>
</html>