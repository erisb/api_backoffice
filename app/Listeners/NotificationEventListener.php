<?php

namespace App\Listeners;

use App\Events\NotificationEvent;
use App\Notification;
use App\Http\Controllers\NotificationController;

class NotificationEventListener
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
    public function handle(NotificationEvent $event)
    {
        $val   = new NotificationController;
        $notif = Notification::all();
        foreach ($notif as $value) {
            $dateCreateEvent = date('Y-m-d', strtotime('+3 day', strtotime($value->created_at)));
            $dateUpdateEvent = date('Y-m-d', strtotime('+3 day', strtotime($value->updated_at)));
            if ($dateCreateEvent < $event->date && $value->position == null) {
                // dd('tester'.$event->date);
                $val->destroyEvent($value->_id);
            }
            if ($dateUpdateEvent < $event->date && $value->position == 5) {
                // dd('tester'.$event->date);
                $val->destroyEvent($value->_id);
            }
            if ($dateUpdateEvent < $event->date && $value->position == 4) {
                // dd('tester'.$event->date);
                $val->destroyEvent($value->_id);
            }
        }
    }
}
