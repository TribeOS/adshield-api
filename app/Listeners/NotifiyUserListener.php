<?php

namespace App\Listeners;

use App\Events\NotifyUser;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Http\Controllers\Adshield\Misc\NotificationController;

class NotifiyUserListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NotifyUser  $event
     * @return void
     */
    public function handle(NotifyUser $event)
    {
        //$event->data - contains all data we want
        if ($event->type == NotificationController::NC_SETTINGS)
        {
            NotificationController::CreateAndSendSettings(
                $event->data['username'],
                $event->data['setting'],
                $event->data['description'],
                $event->data['accountId']
            );
        }
        else if ($event->type == NotificationController::NC_VIOLATIONS)
        {
            NotificationController::CreateAndSend(
                $event->data['userKey'], 
                NotificationController::NC_VIOLATIONS, 
                [
                    'violation' => $event->data['violation']
                ]
            );
        }
    }
}
