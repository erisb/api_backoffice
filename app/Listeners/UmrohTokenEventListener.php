<?php

namespace App\Listeners;

use App\Events\UmrohTokenEvent;
use App\Http\Controllers\APIEksternal\PergiUmrohController;

class UmrohTokenEventListener
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
    public function handle(UmrohTokenEvent $event)
    {
        
        $umroh = new PergiUmrohController;

        date_default_timezone_set("Asia/Jakarta");
        $timeNow = date("Y-m-d");
        $time = $event->time;

        // dd(' Umroh Token :: '.$time. ' Now :: '.$timeNow);
        if ($timeNow >= $time )
        {
            // dd('tester'.$time);
            $umroh->authenticationLogin();
        }
    }
}
