<?php

namespace App\Events;

class BackOfficeUserLogEvent extends Event
{
    public $email,$modul,$status,$aktifitas;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($email,$modul,$aktifitas,$status)
    {
        $this->email = $email;
        $this->modul = $modul;
        $this->status = $status;
        $this->aktifitas = $aktifitas;
    }
}
