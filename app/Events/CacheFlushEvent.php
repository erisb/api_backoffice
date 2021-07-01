<?php

namespace App\Events;

class CacheFlushEvent extends Event
{
    public $tgl;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tgl)
    {
        $this->tgl = $tgl;
    }
}
