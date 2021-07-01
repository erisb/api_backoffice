<?php

namespace App\Listeners;

use App\Events\BackOfficeUserLogEvent;
use App\BackOfficeUserLogs;
use App\BackOfficeUsers;

class BackOfficeUserLogListener
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
     */
    public function handle(BackOfficeUserLogEvent $event)
    {
        $email = $event->email;
        $modul = $event->modul;
        $status = $event->status;
        $aktifitas = $event->aktifitas;
        $data = [
            'modul' => $modul,
            'status' => $status,
            'activity' => $aktifitas
        ];
        $dataUser = BackOfficeUsers::where('emailUser', $email)->first();
        if ($dataUser != null)
        {
            $dataUser->log_user_back_office()->save(new BackOfficeUserLogs($data));
        }
        else {
            $logUser = new BackOfficeUserLogs();
            $logUser->idUserBackOffice = null;
            $logUser->activity = $aktifitas;
            $logUser->status = $status;
            $logUser->modul = $modul;

            $logUser->save();
        }
    }
}
