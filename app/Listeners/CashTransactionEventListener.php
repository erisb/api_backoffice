<?php

namespace App\Listeners;

use App\Events\CashTransactionEvent;
use App\CashTransactions;
use App\Http\Controllers\CashTransactionController;

class CashTransactionEventListener
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
    public function handle(CashTransactionEvent $event)
    {
        $cash = new CashTransactionController;
        $trans = CashTransactions::all();
        foreach ($trans as $value) {

            if ($value->status == "BELUM BAYAR" || $value->status == "Waiting for Deposit" || $value->status == "Waiting for Withdrawal") {
                $dateCreateEvent = date('Y-m-d', strtotime('+2 day', strtotime($value->created_at)));
                // dd('tester '.$dateCreateEvent);
                if ($dateCreateEvent < $event->date) {
                    // dd('tester '.$dateCreateEvent.'<br>tester '.$event->date);
                    $cash->eventDestroy($value->_id);
                }
            }
        }
    }
}
