<?php

namespace App\Listeners;

use App\Events\TopupEvent;
use App\TransactionTopup;
use App\LogTransaction;
use App\Http\Controllers\TransactionTopupController;

class TopupEventListener
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
    public function handle(TopupEvent $event)
    {
        $topup = new TransactionTopupController;
        $notif = TransactionTopup::where('messageTopup', 'BELUM BAYAR')->get();
        foreach ($notif as $value) {
            $dateCreateEvent = date('Y-m-d', strtotime('+2 day', strtotime($value->created_at)));
            if ($dateCreateEvent < $event->date && $value->balanceTopup == "") {
                // dd('tester '.$dateCreateEvent.'<br>tester '.$event->date);
                $topup->eventDestroy($value->_id);
            }
        }
    }
}
