var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var Redis = require('ioredis');

//hold all channels that will be handled (this will contain all the channels all UI clients are accessing)
var channels = {};

io.on('connection', function(socket) {

	socket.connected_channels = {}

	socket.on("subscribe", function(channel) {
		//channel already exists in our list
		if (channels.hasOwnProperty(channel)) {
			console.log("existing channel : " + channel);
			if (typeof channels[channel].listeners == "undefined") cahnnels[channel].listeners = {};
			channels[channel].listeners[socket.id] = socket;
		} else {
			console.log("new channel : " + channel);
			//channel doesn't exists yet, create a new client/handler
			channels[channel] = {};
			channels[channel].redis = new Redis();
			channels[channel].redis.subscribe(channel, function(err, count) {});
			channels[channel].listeners = {};
			channels[channel].listeners[socket.id] = socket;
			channels[channel].redis.on("message", function(channel, message) {
				Object.keys(channels[channel].listeners).forEach(function(key) {
		          	console.log("Sent on " + channel + ": " + message);
		          	channels[channel].listeners[key].send(message);
		        });
			});
		}

		socket.connected_channels[channel] = channels[channel];
	});


	socket.on("disconnect", function() {
		Object.keys(socket.connected_channels).forEach(function(channel) {
	      	delete channels[channel].listeners[socket.id];
	    });
	});
	
});

http.listen(3000, function(){
    console.log('Listening on Port 3000');
});