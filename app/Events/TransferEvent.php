<?php

namespace App\Events;

class TransferEvent extends Event
{
    public $tgl;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($tgl)
    {
        $this->date = $tgl;
    }
}
