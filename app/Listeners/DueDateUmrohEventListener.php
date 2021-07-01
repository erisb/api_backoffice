<?php

namespace App\Listeners;

use App\Events\DueDateUmrohEvent;
use App\Http\Controllers\APIEksternal\PergiUmrohController;

class DueDateUmrohEventListener
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
    public function handle(DueDateUmrohEvent $event)
    {
        $tglSekarang = $event->date;
        // dd('tester'.$tglSekarang);
        $umroh = new PergiUmrohController;

        $umroh->dueDate($tglSekarang);
    }
}
