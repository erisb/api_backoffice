<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Doa;
use App\Bookmark;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class DoaController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['insert','update','destroy']]);
        $this->middleware('onlyJson',['only'=>['search','detail','getDataDoaBySearch','insert','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function search(Request $req){
        try {
            $key = Str::of(Cache::get('key', 'doa_search:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('doa_search:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                return Doa::where('prayerStatus','1')->where('prayerTitle','like',"%".$req->q."%")->get();
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

    public function view(){
        try {
            $key = Str::of(Cache::get('key', 'doa:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('doa:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return json_decode(Doa::where('prayerStatus','1')->orderBy('created_at', 'DESC')->get());
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

    public function detail(Request $req, $id){
        try {
            $key = Str::of(Cache::get('key', 'doa:' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $cek = Bookmark::where('imei', $req->imei)->count();
                if($cek > 0){
                    $isBookmark = 1;
                }else{
                    $isBookmark = 0;
                }
            $cache = Cache::remember('doa:' . $id, env('CACHE_DURATION'), function () use ($req, $id){
                return Doa::where('_id', $id)->first();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'isBookmark' => $isBookmark, 'data' => $cache]);
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
            $data = new Doa;
            $data->prayerTitle          =  $req->prayerTitle;
            $data->indonesianVersion    =  $req->indonesianVersion;
            $data->arabVersion          =  $req->arabVersion;
            $data->prayerSource          =  $req->prayerSource;
            $data->prayerStatus          =  $req->prayerStatus;
            if ($data->save()) {
                Cache::forget('doa:' . date('Y-m-d'));
                Cache::forget('doa_search:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data'=> $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Doa"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Doa','Add Doa - '.$req->prayerTitle,json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        try {
            $data = Doa::where('_id', $id)->first();

            $data->prayerTitle          =  $req->prayerTitle;
            $data->indonesianVersion    =  $req->indonesianVersion;
            $data->arabVersion          =  $req->arabVersion;
            $data->prayerSource          =  $req->prayerSource;
            $data->prayerStatus          =  $req->prayerStatus;
            if ($data->save()) {
                Cache::forget('doa:' . date('Y-m-d'));
                Cache::forget('doa_search:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Doa"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Doa','Update Doa - '.$req->prayerTitle,json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = Doa::where('_id', $id)->first();
            $judulDoa = $data->prayerTitle;

            if ($data->delete()) {
                Cache::forget('doa:' . date('Y-m-d'));
                Cache::forget('doa_search:' . date('Y-m-d'));
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
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Doa','Delete Doa - '.$judulDoa,json_decode($response)->message));
        return $response;
    }

    public function getDataDoaFirstPage($take)
    {
        try {
            $results = Doa::skip(0)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = Doa::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results, 'total' => $totalData]);
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

    public function getDataDoaByPage($take,$page)
    {
        $skip = ($take*$page)-$take;
        try {
            $results = Doa::skip($skip)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = Doa::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results, 'total' => $totalData]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataDoaBySearch(Request $req)
    {
        // $val = str_replace(' ','',$req->search);
        try {
            $results = Doa::where('prayerTitle','like','%'.$req->search.'%')->orderBy('_id','desc')->get();
            // print_r($resultsDoa);die;
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
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
