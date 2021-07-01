<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\UserMobiles;
use App\Http\Controllers\Auth\LoginController;
use App\Events\LogUserMobileEvent;

class CheckValidation extends Controller
{
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['cekValidasiOtp','cekValidasiPin']]);
    }

    private function logUserMobile($data1,$data2,$data3)
    {
        return event(new LogUserMobileEvent($data1,$data2,$data3));
    }

    public function cekValidasiNoTelp($noTelp)
    { 
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();

            if ($user != null && isset($user->pinUser) == true) {
                $otp = $user->user_otp()->first();
                if (isset($otp) == false) {
                    if ($user != null && $otp->statusOTPByUser != 0) {
                        $response = json_encode(array('statusCode' => '178', 'message' => "Input OTP Gagal"));
                    } else {
                        $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                    }
                } else {
                    $response = json_encode(array('statusCode' => '101', 'message' => "Nomor telfon sudah terdaftar, silahkan masukan nomor yang lain"));
                }
            } elseif ($user != null && isset($user->pinUser) == false) {
                $response = json_encode(array('statusCode' => '150', 'message' => "Belum setting PIN"));
            } else {
                $response = json_encode(array('statusCode' => '106', 'message' => "Nomor telfon belum Terdaftar"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp,'Validasi No Telp - '.$noTelp,json_decode($response)->message);
        return $response;
    }

    public function cekValidasiOtp(Request $req)
    {
        $otp = (int) $req->kodeOTP;
        $noTelp = $req->noTelpUser;
        
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            $userOtp = $user != '' ? $user->user_otp()->first() : 0;
            
            if ($user) {
                if ($user->user_otp()->where('kodeOTP', $otp)->count() > 0) {

                    $userOtp->statusOTPByUser  = 0;
                    $userOtp->save();
    
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
    
                    $userOtp->statusOTPByUser  = 1;
                    $userOtp->save();
    
                    $response = json_encode(array('statusCode' => '102', 'message' => "OTP Salah"));
                }
            } else {
                $response = json_encode(array('statusCode' => '106', 'message' => "Nomor telfon belum Terdaftar"));
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp,'Validasi OTP - '.$otp,json_decode($response)->message);
        return $response;
    }

    private function loginSaveToken($noTelp)
    {
        $save = new LoginController;
        return $save->saveToken($noTelp);
    }

    public function cekValidasiPin(Request $req)
    {
        // if ($this->checkImei($req) > 0) {
            
            $login = new LoginController;
            $pin = $req->pinUser;
            $noTelp = $req->noTelpUser;
            
            try {
                $userCount = UserMobiles::where(['pinUser' => $pin, 'noTelpUser' => $noTelp])->count();
                
                if ($userCount > 0) {
                    $user = UserMobiles::where(['pinUser' => $pin, 'noTelpUser' => $noTelp])->first();
                    if ($user) {
                        $login->logout($req);
                        if (json_decode($this->loginSaveToken($noTelp))->statusCode == '000') {
                            $getToken = $user->user_token['token'];
                            $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'token' => $getToken, 'id' => $user->_id));
                        } else {
                            $response = $this->loginSaveToken($noTelp);
                        }
                    }
                } else {
                    $response = json_encode(array('statusCode' => '103', 'message' => "PIN Anda salah, silahkan coba kembali"));
                }
                
            } catch (\Exception $e) {
                $errorCode = $e->getCode();
                $message = $e->getMessage();
                \Sentry\captureException($e);
                $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
            }
            $this->logUserMobile($noTelp,'Validasi PIN - '.$pin,json_decode($response)->message);
            return $response;
        // }else {
        //     return json_encode(array('statusCode' => '522', 'message' => "Imei Belum Terdaftar"));
        // }
    }

    public function cekValidasiDaftarEmail($email)
    {
        try {
            $user = UserMobiles::where('emailUser', $email)->count();

            if ($user > 0) {
                $response = json_encode(array('statusCode' => '224', 'message' => "Email sudah terdaftar, silahkan masukan Email yang lain"));
            } else {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($email,'Validasi Email - '.$email,json_decode($response)->message);
        return $response;
    }

    public function validationTelp($noTelp)
    {
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->count();
            
            if ($user > 0) {
                return json_encode(array('statusCode' => '224', 'message' => "Nomor Telpon sudah terdaftar, silahkan masukan nomor yang lain"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function validateEmptyTelp(Request $req)
    {
        $emptyTelp = Validator::make($req->all(), [
            'noTelpUser'    => 'required',
        ],[
            'noTelpUser.required'   => 'No Telp tidak boleh kosong',
        ]);
        if ($emptyTelp->fails()) {
            return json_encode(['statusCode' => '222', 'message' => implode(" ", $emptyTelp->messages()->all())]);
        }
    }

    public function validateEmptyEmail(Request $req)
    {
        $emptyEmail = Validator::make($req->all(), [
            'emailUser'     => 'required',
        ],[
            'emailUser.required'    => 'Email User tidak boleh kosong',
        ]);
        if ($emptyEmail->fails()) {
            return json_encode(['statusCode' => '222', 'message' => implode(" ", $emptyEmail->messages()->all())]);
        }
    }

    public function validateEmptyNama(Request $req)
    {
        $emptyNama = Validator::make($req->all(), [
            'namaUser'      => 'required',
        ],[
            'namaUser.required'     => 'Nama User tidak boleh kosong',
        ]);
        if ($emptyNama->fails()) {
            return json_encode(['statusCode' => '222', 'message' => implode(" ", $emptyNama->messages()->all())]);
        }
    }

    public function validateEmptyImei(Request $req)
    {
        $emptyNama = Validator::make($req->all(), [
            'imei'      => 'required',
        ],[
            'imei.required'     => 'Imei User tidak boleh kosong',
        ]);
        if ($emptyNama->fails()) {
            return json_encode(['statusCode' => '222', 'message' => implode(" ", $emptyNama->messages()->all())]);
        }
    }

    public function checkImei(Request $req)
    {
        
        try {
            $imei = UserMobiles::where('imei', $req->imei)->count();

            if ($imei > 0) {
                return $imei;
            } else {
                return $imei;
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function minMaxTransfer(Request $req)
    {
        
        try {
            if ((int) $req->nominal < 10000) {
                return json_encode(['statusCode' => '773', 'message' => 'Minimal transfer Rp. 10.000']);
            }
            if ((int) $req->nominal > 999999999999) {
                return json_encode(['statusCode' => '778', 'message' => 'Maksimal transfer Rp. 999.999.999.999']);
            }
            return json_encode(['statusCode' => '000', 'message' => 'Sukses']);
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
}
