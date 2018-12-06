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
    private $accountId;

    /**
     * Create a new event instance.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function __construct($stats, $accountId)
    // public function __construct($stats, $accountId)
    {
        $this->stats = $stats;
        $this->accountId = $accountId;
    }

    public function broadcastOn()
    {
        // return new Channel('adshield.' . $this->accountId);
        return new Channel('adshield.1');
    }

}