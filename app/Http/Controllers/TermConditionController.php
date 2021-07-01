<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\TermConditions;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class TermConditionController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice', ['only' => ['save', 'update', 'destroy']]);
        $this->middleware('onlyJson',['only'=>['save','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function save(Request $req)
    {
        $data = new TermConditions;
        try {
            $data->termContent =  $req->termContent;
            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Success"));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan TermCondition"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        Cache::forget('termcontents');
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        try {
            $data = TermConditions::where('_id', $id)->first();
            $data->termContent =  $req->termContent;

            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Success"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        Cache::forget('termcontents');
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = TermConditions::where('_id', $id)->first();
            
            if ($data->delete()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Success"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        Cache::forget('termcontents');
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }

    public function getData()
    {
        try {
            $key = Str::of(Cache::get('key', 'termcontents:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('termcontents:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                $term = TermConditions::get();
                $arr = [];
                foreach ($term as $value) {
                    array_push($arr, [
                        "content"   => $value->termContent
                    ]);
                }
                return $arr;
            });
            if ($cache) {
                return $cache;
            } else {
                return $cache;
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function getDataTermFirstPage($take)
    {
        try {
            $results = TermConditions::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = TermConditions::count();
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

    public function getDataTermByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = TermConditions::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = TermConditions::count();
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
}
