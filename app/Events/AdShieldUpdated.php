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
    public function __construct($stats)
    // public function __construct($stats, $accountId)
    {
        $this->stats = $stats;
        $this->token = $stats['adshieldstats']['token'];
    }

    public function broadcastOn()
    {
        return new Channel('adshield.' . $this->token);
        // return new Channel('adshield');
    }

}