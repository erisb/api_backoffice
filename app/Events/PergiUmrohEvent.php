<?php

namespace App\Events;

class PergiUmrohEvent extends Event
{
    public $tgl;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($time)
    {
        $this->time = $time;
    }
}
