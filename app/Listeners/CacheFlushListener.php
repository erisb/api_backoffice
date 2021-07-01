<?php

namespace App\Listeners;

use App\Events\CacheFlushEvent;
use Illuminate\Support\Facades\Cache;

class CacheFlushListener
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
    public function handle(CacheFlushEvent $event)
    {
        $tglSekarang = $event->tgl;
        if (strtotime($tglSekarang) < strtotime(date('Y-m-d')))
        {
            Cache::flush();
        }
    }
}
