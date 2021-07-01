<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\CheckValidation;
use App\Http\Controllers\Auth\LoginController;
use App\UserMobiles;
use App\Events\LogUserMobileEvent;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['saveRegister']]);
    }

    private function logUserMobile($data1,$data2,$data3)
    {
        return event(new LogUserMobileEvent($data1,$data2,$data3));
    }

    // Register-Validasi No Tlp Terdaftar/tdk
    public function regValidasiCekTlp($noTelp)
    {
        $validNoTelp = new CheckValidation;
        return $validNoTelp->cekValidasiNotelp($noTelp);
    }

    public function regGenerateOTP($noTelp)
    {
        $otp = new LoginController;
        return $otp->generateOTP($noTelp);
    }

    public function validasiDaftarEmail(Request $req)
    {
        $validEmail = new CheckValidation;
        return $validEmail->cekValidasiDaftarEmail($req->emailUser);
    }

    public function saveRegister(Request $req)
    {
        
        $validTelp = new CheckValidation;
        
        if (!empty($validTelp->validateEmptyTelp($req))) {
            return $validTelp->validateEmptyTelp($req);
        }
        if (!empty($validTelp->validateEmptyEmail($req))) {
            return $validTelp->validateEmptyEmail($req);
        }
        if (!empty($validTelp->validateEmptyNama($req))) {
            return $validTelp->validateEmptyNama($req);
        }
        if (!empty($validTelp->validationTelp($req->noTelpUser))) {
            return $validTelp->validationTelp($req->noTelpUser);
        }
        if (!empty($validTelp->validateEmptyImei($req))) {
            return $validTelp->validateEmptyImei($req);
        }
        $email  = $req->emailUser;
        $noTelp = $req->noTelpUser;
        $nama   = $req->namaUser;
        $imei   = $req->imei;

        $validatorPanjang = Validator::make($req->all(), UserMobiles::$rulesNoTelpMin, UserMobiles::$messages);
        $validatorAngka = Validator::make($req->all(), UserMobiles::$rulesNoTelpNumeric, UserMobiles::$messages);
        $validatorEmail = Validator::make($req->all(), UserMobiles::$rulesEmail, UserMobiles::$messages);

        try {

            if ($validatorPanjang->fails()) {
                $response = json_encode(['statusCode' => '222', 'message' => implode(" ", $validatorPanjang->messages()->all())]);
            } else if ($validatorAngka->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorAngka->messages()->all())]);
            } else if ($validatorEmail->fails()) {
                $response = json_encode(['statusCode' => '223', 'message' => implode(" ", $validatorEmail->messages()->all())]);
            } else if (json_decode($this->validasiDaftarEmail($req))->statusCode == '000') {
                if (json_decode($this->regValidasiCekTlp($noTelp))->statusCode == '106') {
                    $data = new UserMobiles;

                    $data->noTelpUser       = $noTelp;
                    $data->emailUser        = $email;
                    $data->namaUser         = $nama;
                    $data->imei             = $imei;
                    $data->flag             = false;
                    $data->nik              = "";
                    $data->urlFotoKtp       = "";
                    $data->urlFotoSelfieKtp = "";
                    $data->statusVerifikasi = "0";

                    if ($data->save()) {
                        if (json_decode($this->regGenerateOTP($noTelp))->statusCode == '000') {
                            $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                        } else {
                            $response = json_encode(array('statusCode' => '109', 'message' => "Gagal Generate OTP"));
                        }
                    } else {
                        $response = json_encode(array('statusCode' => '325', 'message' => "Gagal Register"));
                    }
                } else {
                    $response = $this->regValidasiCekTlp($noTelp);
                }
            } else {
                $response = $this->validasiDaftarEmail($req);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp,'Register',json_decode($response)->message);
        return $response;
    }
}
