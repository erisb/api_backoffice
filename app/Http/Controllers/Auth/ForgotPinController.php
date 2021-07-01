<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\UserMobiles;
use App\Events\LogUserMobileEvent;

class ForgotPinController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['forgotPinSendOTP']]);
    }

    private function logUserMobile($data1,$data2,$data3)
    {
        return event(new LogUserMobileEvent($data1,$data2,$data3));
    }

    public function forgotPinSendOTP(Request $req)
    {
        $noTelp = $req->noTelpUser;
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            if ($user != null) {
                if ($user->user_token()) {
                    $user->user_token()->delete();
                    $user->user_otp()->delete();
                    $otp = new LoginController;
                    $response = $otp->generateOTP($req->noTelpUser);
                }else {
                    $user->user_otp()->delete();
                    $otp = new LoginController;
                    $response = $otp->generateOTP($req->noTelpUser);
                }
            } else {
                $response = json_encode(array('statusCode' => '106', 'message' => "No Telp belum Terdaftar"));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp,'Forgot PIN',json_decode($response)->message);
        return $response;
    }
}
