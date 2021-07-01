<?php

namespace App\Listeners;

use App\Events\LogUserMobileEvent;
use App\LogUserMobiles;
use App\UserMobiles;

class LogUserMobileListener
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
    public function handle(LogUserMobileEvent $event)
    {
        $noTelp = $event->noTelp;
        $status = $event->status;
        $aktifitas = $event->aktifitas;
        $data = [
            'status' => $status,
            'activity' => $aktifitas
        ];
        $dataUser = UserMobiles::where('noTelpUser', $noTelp)->first();
        if ($dataUser != null)
        {
            $dataUser->log_user_mobile()->save(new LogUserMobiles($data));
        }
        else {
            $logUser = new LogUserMobiles();
            $logUser->idUserMobile = null;
            $logUser->activity = $aktifitas;
            $logUser->status = $status;

            $logUser->save();
        }
    }
}
