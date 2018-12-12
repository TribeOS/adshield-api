var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var Redis = require('ioredis');
var redis = new Redis();

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
		} else {
			//channel doesn't exists yet, create a new client/handler
			channels[channel] = redis.createClient();
			channels[channel].subscribe(channel);
			channels[channel].listeners = {};
			channels[channel].listeners[socket.id] = socket;
			channels[channel].on("message", function(channel, message) {
				for(var i in channels[channel].listeners) {
					console.log("Sent on " + channel + ": " + message);
					channels[channel].listeners[i].send(message);
				}
			});
		}
	});


	
});

http.listen(3000, function(){
    console.log('Listening on Port 3000');
});