<?php

namespace App\Http\Controllers\Auth\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\BackOfficeUsers;
use App\BackOfficeUserTokens;
use App\Events\BackOfficeUserLogEvent;

class CheckValidation
{
    private function logUserBackoffice($data1,$data2,$data3,$data4)
    {
        return event(new BackOfficeUserLogEvent($data1,$data2,$data3,$data4));
    }

    public function cekValidasiLogin($email,$pass)
    {
        //    
        try {
            $cekUser = BackOfficeUsers::where('emailUser', $email)->first();
            
            if ($cekUser != null && Hash::check($pass, $cekUser['passwordUser'])) {
                $response = $this->saveToken($email);
            } else {
                $response = json_encode(array('statusCode' => '101', 'message' => "Email/Password Salah"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Login','Validasi Login - '.$email,json_decode($response)->message);
        return $response;
    }

    public function cekValidasiDaftarEmail($email)
    {
        //    
        try {
            $user = BackOfficeUsers::where('emailUser', $email)->count();
            if ($user > 0) {
                $response = json_encode(array('statusCode' => '224', 'message' => "Email sudah terdaftar"));
            } else {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Register','Validasi Email - '.$email,json_decode($response)->message);
        return $response;
    }

    public function cekValidasiPassLama(Request $req)
    {
        $email = $req->emailUser;
        $pass = $req->passwordUser;
        try {
            $cekUser = BackOfficeUsers::where('emailUser',$email)->first();
            if (Hash::check($pass, $cekUser['passwordUser'])) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '126', 'message' => "Password tidak terdaftar"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Validasi Password','Validasi Password - '.$email,json_decode($response)->message);
        return $response;
    }

    public function saveToken($email)
    {
        try {
            $user = BackOfficeUsers::where('emailUser',$email)->first();
            $namaRole = $user->user_role ? $user->user_role->namaRole : '';
            $arrMenu = [];
            $listMenu = $user->user_role ? $user->user_role->listMenu : [];
            foreach ($listMenu as $index => $val) {
                array_push($arrMenu,['noMenu'=>$listMenu[$index]['noMenu'],'namaMenu'=>$listMenu[$index]['namaMenu'],'urlMenu'=>$listMenu[$index]['urlMenu'],'iconMenu'=>$listMenu[$index]['iconMenu']]);
            }
            $keys = array_column($arrMenu, 'noMenu');
            array_multisort($keys, SORT_ASC, $arrMenu);

            if (!$token = $user->getTokenAttribute()) {
                return response()->json(['user_not_found'], 404);
            }
            
            $saveToken = $user != null ? $user->user_token()->save(new BackOfficeUserTokens(['token' => $token, 'expired' => null])) : null;
            $dataUser = ['id'=>$user->user_token['idUserBackOffice'],'token'=>$user->user_token['token'],'nama'=>$user->namaUser,'email'=>$user->emailUser,'role'=>$namaRole,'menu'=>$arrMenu,'defaultUrl'=>$arrMenu[0]['urlMenu']];
            
            if ($saveToken) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data_user' => $dataUser));
            } else {
                $response = json_encode(array('statusCode' => '108', 'message' => "Gagal Simpan Token", 'token' => null));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Login','Save Token - '.$email,json_decode($response)->message);
        return $response;
    }
}
