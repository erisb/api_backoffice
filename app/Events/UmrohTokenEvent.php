<?php

namespace App\Events;

class UmrohTokenEvent extends Event
{
    public $tokenTime;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tokenTime)
    {
        $this->time = $tokenTime;
    }
}
