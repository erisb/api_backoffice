<?php

namespace App\Http\Controllers\Auth\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\BackOfficeUsers;
use App\Http\Controllers\Auth\BackOffice\CheckValidation;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\BackOfficeUserTokens;
use App\Events\BackOfficeUserLogEvent;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['login','logout']]);
    }

    private function logUserBackoffice($data1,$data2,$data3,$data4)
    {
        return event(new BackOfficeUserLogEvent($data1,$data2,$data3,$data4));
    }

    public function login(Request $req)
    {
        $email = $req->emailUser;
        $pass = $req->passwordUser;
        
        $validatorFormatEmail = Validator::make($req->all(), BackOfficeUsers::$rulesEmail, BackOfficeUsers::$messages);
        try {
            if ($email != null && $validatorFormatEmail->fails()) {
                $response = json_encode(['statusCode' => '124', 'message' => implode(" ", $validatorFormatEmail->messages()->all())]);
            } else {
                $response = $this->validasiLogin($email,$pass);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Login','Login - '.$email,json_decode($response)->message);
        return $response;
    }

    private function validasiLogin($email,$pass)
    {
        $cek = new CheckValidation;
        return $cek->cekValidasiLogin($email,$pass);
    }

    public function logout(Request $req)
    {
        $email = $req->emailUser;
        try {
            $user = BackOfficeUsers::where('emailUser', $email)->first();
            if ($user != null && $user->user_token()->count() > 0) {
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
        $this->logUserBackOffice($email,'Logout','Logout - '.$email,json_decode($response)->message);
        return $response;
    }

    public function cekToken(Request $req)
    {
        $userToken = BackOfficeUserTokens::where('token',$req->token)->first();
        $email = $userToken != '' ? $userToken->user_back_office->emailUser : '';
        if ($email != '') {
            try 
            {
                $token = JWTAuth::getToken();
                $a = JWTAuth::getPayload($token)->toArray();
            } catch (TokenExpiredException $e) {
                event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Token Expired'));
                $userToken->delete();
                \Sentry\captureException($e);
                return json_encode(array('statusCode' => '555', 'message' => "Token Expired"));
            } catch (TokenInvalidException $e) {
                event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Invalid Token'));
                \Sentry\captureException($e);
                return json_encode(array('statusCode' => '666', 'message' => "Invalid Token"));
            } catch (JWTException $e) {
                event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Token Absent'));
                \Sentry\captureException($e);
                return json_encode(array('statusCode' => '777', 'message' => "Token Absent"));
            }
            return json_encode(array('statusCode' => '000', 'message' => "Token Valid", 'token' => JWTAuth::getToken()));
        }
        else {
            return json_encode(array('statusCode' => '888', 'message' => "Token Kosong"));
        }
        
    }

}
