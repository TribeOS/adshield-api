<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdShieldUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $stats;
    private $token;

    /**
     * Create a new event instance.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function __construct($stats, $token)
    // public function __construct($stats, $accountId)
    {
        $this->stats = $stats;
        $this->token = $token;
    }

    public function broadcastOn()
    {
        return new Channel('adshield.1');
        // return new Channel('adshield');
    }

}