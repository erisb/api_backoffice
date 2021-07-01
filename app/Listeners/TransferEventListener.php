<?php

namespace App\Listeners;

use App\Events\TransferEvent;
use App\TransferTransactions;
use App\LogTransaction;
use App\Http\Controllers\TransferTransactionsController;

class TransferEventListener
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
    public function handle(TransferEvent $event)
    {
        $tf = new TransferTransactionsController;
        $notif = TransferTransactions::where('trxId', null)->get();
        foreach ($notif as $value) {
            $dateCreateEvent = date('Y-m-d', strtotime('+2 day', strtotime($value->created_at)));
            
            if ($dateCreateEvent < $event->date && $value->balanceTopup == "") {
                // dd('tester '.$dateCreateEvent.'<br>tester '.$event->date);
                $tf->eventDestroy($value->_id);
            } else if ($value->spsBank == "") {
                TransferTransactions::where('_id', $value->_id)->delete();
            }
        }
    }
}
