<?php

namespace App\Events;

class CartsEvent extends Event
{
    public $date;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($date)
    {
        $this->date = $date;
    }
}
