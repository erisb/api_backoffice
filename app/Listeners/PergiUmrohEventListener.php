<?php

namespace App\Listeners;

use App\Events\PergiUmrohEvent;
use App\Http\Controllers\APIEksternal\PergiUmrohController;

class PergiUmrohEventListener
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
    public function handle(PergiUmrohEvent $event)
    {

        $umroh = new PergiUmrohController;

        date_default_timezone_set("Asia/Jakarta");
        $timeNow = date("Y-m-d h:i:sa");
        $time = $event->time;
        if ($timeNow >= $time) {
            // dd('tester'.$time);
            $umroh->packageInsert();
        }
    }
}
