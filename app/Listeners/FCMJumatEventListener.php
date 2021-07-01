<?php

namespace App\Listeners;

use App\Events\FCMJumatEvent;
use App\Http\Controllers\APIEksternal\FCMController;

class FCMJumatEventListener
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
     * @param  \App\Events\ExampleEvent  $event
     * @return void
     */
    public function handle(FCMJumatEvent $event)
    {
        date_default_timezone_set('Asia/Jakarta');
        if (date("l", strtotime($event->date)) == "Friday") {
            $fcm = new FCMController;
            if (date("h:i:s") == "06:00:00") {
                $fcm->sendMessageJumatan($date);
            }
            if (date("h:i:s") == "09:00:00") {
                $fcm->sendMessageJumatan($date);
            }
            if (date("h:i:s") == "11:00:00") {
                $fcm->sendMessageJumatan($date);
            }
        }
    }
}
