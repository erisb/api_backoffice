<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\UserMobiles;
use App\UserOtps;
use App\UserTokens;
use App\LogGuests;
use App\Http\Controllers\Auth\CheckValidation;
use App\Http\Controllers\APIEksternal\GatewayController;
use App\Events\LogUserMobileEvent;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['login','savePin','generateOTP','kirimulang','logout','saveLogGuest','updateOtp','updateImei']]);
    }

    private function logUserMobile($data1, $data2, $data3)
    {
        return event(new LogUserMobileEvent($data1, $data2, $data3));
    }

    public function login(Request $req)
    {
        $noTelpUser = $req->noTelpUser;
        $validatorPanjang = Validator::make($req->all(), UserMobiles::$rulesNoTelpMin, UserMobiles::$messages);
        $validatorAngka = Validator::make($req->all(), UserMobiles::$rulesNoTelpNumeric, UserMobiles::$messages);
        try {
            if ($validatorPanjang->fails()) {
                $response = json_encode(['statusCode' => '222', 'message' => implode(" ", $validatorPanjang->messages()->all())]);
            } else if ($validatorAngka->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorAngka->messages()->all())]);
            } else {
                $response = $this->validasiNotelp($noTelpUser);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelpUser, 'Login', json_decode($response)->message);
        return $response;
    }

    public function validasiNotelp($noTelp)
    {
        $cek = new CheckValidation;
        return $cek->cekValidasiNotelp($noTelp);
    }

    public function savePin(Request $req)
    {
        $checkImei = new CheckValidation;

        $validatorPinNum    = Validator::make($req->all(), UserMobiles::$rulesPinNumeric, UserMobiles::$messages);
        $validatorPin       = Validator::make($req->all(), UserMobiles::$rulesPinUser, UserMobiles::$messages);
        $validatorPanjang   = Validator::make($req->all(), UserMobiles::$rulesNoTelpMin, UserMobiles::$messages);
        $validatorMinPin    = Validator::make($req->all(), UserMobiles::$rulesPinMin, UserMobiles::$messages);

        $pin = $req->pinUser;
        $telp = $req->noTelpUser;
        
        try {
            if ($validatorPanjang->fails()) {
                $response = json_encode(['statusCode' => '222', 'message' => implode(" ", $validatorPanjang->messages()->all())]);
            } else if ($validatorPin->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorPin->messages()->all())]);
            }else if ($validatorPinNum->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorPinNum->messages()->all())]);
            }else if ($validatorMinPin->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorMinPin->messages()->all())]);
            } else {
                $user = UserMobiles::where('noTelpUser', $telp)->first();
                if ($user) {
                    $count = UserOtps::where('idUserMobile', $user->_id)->where('statusOTPByUser', 0)->count();
                    if ($count > 0) {
                        $data = array(
                            'pinUser' => $pin,
                        );
                        $user->user_token()->delete();
                        if (UserMobiles::where('noTelpUser', $telp)->update($data)) {
                            if (json_decode($this->saveToken($telp))->statusCode == '000') {
                                $user = UserMobiles::where('noTelpUser', $telp)->first();
                                $getToken = $user->user_token['token'];
                                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'token' => $getToken, 'id' => $user->_id));
                            } else {
                                $response = $this->saveToken($telp);
                            }
                        } else {
                            $response = json_encode(array('statusCode' => '110', 'message' => "Gagal Simpan PIN"));
                        }
                    }else {
                        $response = json_encode(array('statusCode' => '322', 'message' => "Belum Input OTP"));
                    }
                } else {
                    $response = json_encode(array('statusCode' => '106', 'message' => "Nomor telfon belum Terdaftar"));
                }
                
            }       
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($telp, 'Save PIN', json_decode($response)->message);
        return $response;
    }

    public function generateOTP($noTelp)
    {
        try {
            $sub = substr($noTelp,0,4);
            $code  = mt_rand(1000, 9999);
            $status = "";
            $gateway = new GatewayController;

            if ("0895" == $sub || "0896" == $sub || "0897" == $sub || "0898" == $sub || "0899" == $sub) {
                $result = $gateway->zenzivaSms($noTelp, $code);
                if ($result == 1) {
                    $status = "Sukses";
                } else {
                    $status = "";
                }
            }else {
                $result = $gateway->smsSend($noTelp, $code);
                if ($result == 0) {
                    $status = "Sukses";
                } elseif ($result == 1) {
                    $status = "Invalid Parameter / Invalid JSON Format";
                } elseif ($result == 2) {
                    $status = "Internal Server Error";
                } elseif ($result == 3) {
                    $status = "Invalid Recipient";
                } elseif ($result == 4) {
                    $status = "Invalid Signature / Account Not Found";
                } elseif ($result == 5) {
                    $status = "Invalid Corporate";
                } elseif ($result == 6) {
                    $status = "Client IP not in Whitelist";
                } elseif ($result == 7) {
                    $status = "Not Enough Token";
                } elseif ($result == 8) {
                    $status = "Invalid Sender Id (sender_id)";
                } elseif ($result == 9) {
                    $status = "Invalid Reference Id (ref_id)";
                } else {
                    $status = "";
                }
            }

            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            $otp = '';
            $userOtp = UserOtps::where('idUserMobile', $user->_id)->count();
            if ($userOtp > 0) {
                $otp = $user->user_otp()->update(['kodeOTP' => $code, 'statusOTP' => $status, 'statusOTPByUser' => 2]);
            } else {
                $otp = $user->user_otp()->save(new UserOtps(['kodeOTP' => $code, 'statusOTP' => $status, 'statusOTPByUser' => 2]));
            }

            if ($otp) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'status' => $status));
            } else {
                $response = json_encode(array('statusCode' => '109', 'message' => "Gagal Generate OTP"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp, 'Generate OTP', json_decode($response)->message);
        return $response;
    }

    public function saveNoTelp(Request $req)
    {
        $noTelp = $req->noTelpUser;

        $validatorPanjang = Validator::make($req->all(), UserMobiles::$rulesNoTelpMin, UserMobiles::$messages);
        $validatorAngka = Validator::make($req->all(), UserMobiles::$rulesNoTelpNumeric, UserMobiles::$messages);

        $checkTelp = new CheckValidation;

        try {
            if ($validatorPanjang->fails()) {
                $response = json_encode(['statusCode' => '222', 'message' => implode(" ", $validatorPanjang->messages()->all())]);
            } else if ($validatorAngka->fails()) {
                $response = json_encode(['statusCode' => '322', 'message' => implode(" ", $validatorAngka->messages()->all())]);
            } else if (json_decode($checkTelp->cekValidasiNoTelp($noTelp))->statusCode == '106') {

                $data = new UserMobiles;

                $data->noTelpUser       = $noTelp;
                $data->emailUser        = $noTelp . "@example.com";
                $data->namaUser         = "User" . $noTelp;
                $data->imei             = $req->imei;
                $data->flag             = false;
                $data->nik              = "";
                $data->urlFotoKtp       = "";
                $data->urlFotoSelfieKtp = "";
                $data->statusVerifikasi = "0";

                if ($data->save()) {
                    $otp = json_decode($this->generateOTP($noTelp));
                    if ($otp->statusCode == '000') {
                        $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data, 'statusOTP' => $otp->status));
                    } else {
                        return $this->generateOTP($noTelp);
                    }
                } else {
                    $response = json_encode(array('statusCode' => '107', 'message' => "Gagal Simpan No Telp"));
                }
            } else {
                return $checkTelp->cekValidasiNoTelp($noTelp);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp, 'Register', json_decode($response)->message);
        return $response;
    }

    public function saveToken($noTelp)
    {
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            if ($user != null) {
                if (!$token = $user->getTokenAttribute()) {
                    return response()->json(['user_not_found'], 404);
                }

                $data = $user->user_token()->save(new UserTokens(['token' => $token, 'expired' => null]));

                if ($data) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '108', 'message' => "Gagal Simpan Token"));
                }
            } else {
                $response = json_encode(['user_not_found'], 404);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp, 'Save Token', json_decode($response)->message);
        return $response;
    }

    public function logout(Request $req)
    {
        $noTelp = $req->noTelpUser;
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            if ($user->user_token()->count() > 0) {
                $user->user_token()->delete();
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '421', 'message' => 'Gagal Logout']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp, 'Logout', json_decode($response)->message);
        return $response;
    }

    public function saveLogGuest(Request $req)
    {
        try {

            $data = new LogGuests;

            $data->ipAddress  = $req->ipAddress;
            $data->jenisHp    = $req->jenisHp;
            $data->jenisOS    = $req->jenisOS;
            $data->imei       = $req->IMEI;
            $data->lokasi     = ['latitude' => $req->latitude, 'longitude' => $req->longitude];

            if ($data->save()) {
                return json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                return json_encode(array('statusCode' => '323', 'message' => "Gagal Simpan Log Guest"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateOtp(Request $req)
    {
        $noTelp = $req->noTelpUser;
        try {
            $user = UserMobiles::where('noTelpUser', $noTelp)->first();
            if ($user) {
                $response = $this->generateOTP($noTelp);
            } else {
                $response = json_encode(array('statusCode' => '106', 'message' => "Nomor telfon belum Terdaftar"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserMobile($noTelp, 'Update OTP', json_decode($response)->message);
        return $response;
    }

    public function updateImei(Request $req)
    {
        try {

            $data = UserMobiles::where('noTelpUser', $req->noTelpUser)->first();

            $data->imei     = $req->imei;

            if ($data->update()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '323', 'message' => "Gagal Update Imei"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
