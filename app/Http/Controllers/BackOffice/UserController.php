<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\BackOfficeUsers;
use App\Events\BackOfficeUserLogEvent;

class UserController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['updateUser','deleteUser','changePassword']]);
        $this->middleware('onlyJson',['only'=>['getDataUserBySearch','updateUser','changePassword']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function getDataUserFirstPage($take)
    {
        try {
            $users = BackOfficeUsers::skip(0)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = BackOfficeUsers::count();
            if ($users) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $users, 'total' => $totalData]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataUserByPage($take,$page)
    {
        $skip = ($take*$page)-$take;
        try {
            $userById = BackOfficeUsers::skip($skip)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = BackOfficeUsers::count();
            if ($userById) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $userById, 'total' => $totalData]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataUserBySearch(Request $req)
    {
        $val = str_replace(' ','',$req->search);
        
        try {
            $result = BackOfficeUsers::where('nameUser','like','%'.$val.'%')->orWhere('emailUser','like','%'.$val.'%')->orderBy('_id','desc')->get();

            if ($result) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function updateUser(Request $req, $id)
    {
        $nama = $req->input('namaUser');
        $email = $req->input('emailUser');
        $role = $req->input('roleUser');
        $pass = $req->input('passwordUser') != '' ? Hash::make($req->input('passwordUser')) : '';
        try {
            if ($pass != '')
            {
                $data = array(
                    'namaUser' => $nama,
                    'emailUser' => $email,
                    'passwordUser' => $pass,
                    'roleUser' => $role,
                );
            }
            else {
                $data = array(
                    'namaUser' => $nama,
                    'emailUser' => $email,
                    'roleUser' => $role,
                );
            }

            if (BackOfficeUsers::where('_id', $id)->update($data)) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '444', 'message' => 'Gagal Update User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'User','Update User - '.$email,json_decode($response)->message));
        return $response;
    }

    public function deleteUser($id)
    {
        try {
            $dataUser = BackOfficeUsers::where('_id', $id)->first();
            $email = $dataUser->emailUser;

            if (BackOfficeUsers::where('_id', $id)->delete()) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '445', 'message' => 'Gagal Delete User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'User','Delete User - '.$email,json_decode($response)->message));
        return $response;
    }

    public function changePassword(Request $req,$id)
    {
        $pass = $req->input('passwordUser') != '' ? Hash::make($req->input('passwordUser')) : '';
        try {
            if (BackOfficeUsers::where('_id', $id)->update(['passwordUser' => $pass])) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '450', 'message' => 'Gagal Change Password User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'User','Change Password',json_decode($response)->message));
        return $response;
    }
}
