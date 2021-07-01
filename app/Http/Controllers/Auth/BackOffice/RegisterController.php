<?php

namespace App\Http\Controllers\Auth\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Auth\BackOffice\CheckValidation;
use App\BackOfficeUsers;
use App\Events\BackOfficeUserLogEvent;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['saveRegister']]);
        $this->middleware('onlyJson',['only'=>['saveRegister']]);
    }

    private function logUserBackoffice($data1,$data2,$data3,$data4)
    {
        return event(new BackOfficeUserLogEvent($data1,$data2,$data3,$data4));
    }

    private function validasiDaftarEmail($email)
    {
        $validEmail = new CheckValidation;
        return $validEmail->cekValidasiDaftarEmail($email);
    }

    public function saveRegister(Request $req)
    {
        $email = $req->input('emailUser');
        $nama = $req->input('namaUser');
        $role = $req->input('roleUser');
        $pass = Hash::make($req->input('passwordUser'));

        $validatorEmail = Validator::make($req->all(), BackOfficeUsers::$rulesEmail, BackOfficeUsers::$messages);

        try {

            if ($email != null && $validatorEmail->fails()) {
                $response = json_encode(['statusCode' => '124', 'message' => implode(" ", $validatorEmail->messages()->all())]);
            } else if (json_decode($this->validasiDaftarEmail($email))->statusCode == '000') {
                
                $data = new BackOfficeUsers;

                $data->emailUser = $email;
                $data->namaUser   = $nama;
                $data->passwordUser = $pass;
                $data->roleUser = $role;

                if ($data->save()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses'));
                } else {
                    $response = json_encode(array('statusCode' => '125', 'message' => "Gagal Register"));
                }

            } else {
                $response = $this->validasiDaftarEmail($email);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        $this->logUserBackOffice($email,'Register','Register - '.$email,json_decode($response)->message);
        return $response;
    }
}
