<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\Bookmark;
use App\Doa;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\NotificationController;

class BookmarkController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['getBookmark','insert']]);
    }
    
    public function getBookmark(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'bookmark:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('bookmark:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                $data = Bookmark::where('imei', $req->imei)->orderBy('created_at')->get();
                $arr_book = [];
                foreach($data as $val){
                    $doa = Doa::where('_id', $val->idDoa)->where('prayerStatus','1')->first();
                    array_push($arr_book,[
                        '_id'           => $doa->_id,
                        'prayerTitle'   => $doa->prayerTitle,
                        'idBookmark'    => $val->_id
                    ]);
                }
                return $arr_book;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '522', 'message' => 'Data Kosong', 'data' => $cache]);
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
            $data = new Bookmark;

            $data->imei          = $req->imei;
            $data->idDoa         = $req->idDoa;
            $cek = Bookmark::where(['imei'=>$req->imei, 'idDoa'=>$req->idDoa])->count();
            if ($cek > 0) {
                $getId = Bookmark::where(['imei'=>$req->imei, 'idDoa'=>$req->idDoa])->first();
                $this->destroy($getId->_id);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses Delete"));
            }else{
                if($data->save()){
                    Cache::forget('bookmark:' . date('Y-m-d'));
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Bookmark"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Add Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = '';
            $data = Bookmark::where('_id', $id)->delete();
            if ($data > 0) {
                Cache::forget('bookmark:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Bookmark"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Delete Inspirasi',json_decode($response)->message));
        return $response;
    }
}
