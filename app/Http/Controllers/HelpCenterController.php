<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\HelpCenters;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class HelpCenterController extends Controller
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
        $this->middleware('onlyJson',['only'=>['search','detail','getDataDoaBySearch','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function listHelpCenter(){
        try {
            $key = Str::of(Cache::get('key', 'helpcenter:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('helpcenter:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return json_decode(HelpCenters::orderBy('created_at', 'DESC')->get());
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
            $data = new HelpCenters;
            $data->idHelpCenter     =  $req->id;
            $data->title            =  $req->title;
            $data->description      =  $req->description;
            $data->flag             =  $req->flag;
            if ($data->save()) {
                Cache::forget('helpcenter:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data'=> $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Help Center"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Help Center','Add Help Center - '.$req->title,json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        try {
            $data = HelpCenters::where('_id', $id)->first();

            $data->idHelpCenter   =  $req->id;
            $data->title          =  $req->title;
            $data->description    =  $req->description;
            $data->flag           =  $req->flag;
            if ($data->save()) {
                Cache::forget('helpcenter:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Help Center"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Help Center','Update Help Center - '.$req->title,json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = HelpCenters::where('_id', $id)->first();
            $title = $data->title;
            if ($data->delete()) {
                Cache::forget('helpcenter:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Help Center','Delete Help Center - '.$title,json_decode($response)->message));
        return $response;
    }
}
