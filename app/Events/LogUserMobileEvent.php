<?php

namespace App\Events;

class LogUserMobileEvent extends Event
{
    public $noTelp,$status,$aktifitas;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($noTelp,$aktifitas,$status)
    {
        $this->noTelp = $noTelp;
        $this->status = $status;
        $this->aktifitas = $aktifitas;
    }
}
