<?php

namespace App\Listeners;

use App\Events\DonasiEvent;
use App\MerchantTransaction;
use App\LogTransaction;
use App\Http\Controllers\MerchantTransactionController;

class DonasiEventListener
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
    public function handle(DonasiEvent $event)
    {
        $topup = new MerchantTransactionController;
        $notif = MerchantTransaction::where('statusTransfer', 'BELUM BAYAR')->get();
        foreach ($notif as $value) {
            $dateCreateEvent = date('Y-m-d', strtotime('+2 day', strtotime($value->created_at)));
            if ($dateCreateEvent < $event->date && $value->balanceTopup == "") {
                // dd('tester '.$dateCreateEvent.'<br>tester '.$event->date);
                $topup->eventDestroy($value->_id);
            }
        }
    }
}
