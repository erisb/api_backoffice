<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\CustomerComplain;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class CustomerComplainController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => []]);
        $this->middleware('onlyJson',['only'=>['search','detail','insert','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function listComplain(){
        try {
            $key = Str::of(Cache::get('key', 'customercomplain:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('customercomplain:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return json_decode(CustomerComplain::orderBy('created_at', 'DESC')->get());
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function listComplainByUser(Request $req){
        try {
            $key = Str::of(Cache::get('key', 'customercomplainbyuser:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('customercomplainbyuser:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req){
                return json_decode(CustomerComplain::where('idUserMobile', $req->idUserMobile)->orderBy('created_at', 'DESC')->get());
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insert(Request $req)
    {
        try {
            $data = new CustomerComplain;

            $data->idUserMobile     =  $req->idUserMobile;
            $data->transactionId    =  $req->transactionId;
            $data->email            =  $req->email;
            $data->transactionType  =  $req->transactionType;
            $data->complain         =  $req->complain;

            if ($data->save()) {
                Cache::forget('customercomplain:' . date('Y-m-d'));
                Cache::forget('customercomplainbyuser:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data'=> $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Keluhan"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Keluhan','Add Keluhan - '.$req->transactionId,json_decode($response)->message));
        return $response;
    }
}
