<?php

namespace App\Listeners;

use App\Events\CartsEvent;
use App\HijrahCarts;
use App\Http\Controllers\APIEksternal\PergiUmrohController;


class CartsEventListener
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
    public function handle(CartsEvent $event)
    {
        $umroh = new PergiUmrohController;
        $cart = HijrahCarts::all();
        foreach ($cart as $value) {
            $dateCreateEvent = date('Y-m-d', strtotime('+1 day', strtotime($value->created_at)));
            if ($dateCreateEvent < $event->date) {
                // dd('tester'.$event->date);
                $umroh->destroyEventCarts($value->_id);
            }
        }
    }
}
