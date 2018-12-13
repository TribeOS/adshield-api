var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var redis = require('ioredis');

//hold all channels that will be handled (this will contain all the channels all UI clients are accessing)
var channels = {};

io.on('connection', function(socket) {


	//client connects
	//wait for on("subscribe")
	//get identifier (passed by client upon "subscribe" msg)
	//issue redis.subscribe to the channel name with identifier

	socket.on("subscribe", function(channel) {
		//channel already exists in our list
		if (channels.hasOwnProperty(channel)) {
			channels[channel].listeners[socket.id] = socket;
			console.log("Join existing channel : " + channel);
		} else {
			console.log("Create channel : " + channel);
			//channel doesn't exists yet, create a new client/handler
			channels[channel] = redis.createClient();
			channels[channel].subscribe(channel);
			channels[channel].listeners = {};
			channels[channel].listeners[socket.id] = socket;
			channels[channel].on("message", function(channel, message) {
				Object.keys(channels[channel].listeners).forEach(function(key) {
		          	console.log("Sent on " + channel + ": " + message);
		          	// channels[channel].listeners[key].send(message);
		        });
			});
		}
	});
	
});

http.listen(3000, function(){
    console.log('Listening on Port 3000');
});