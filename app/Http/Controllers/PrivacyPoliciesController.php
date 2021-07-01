<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\PrivacyPolicies;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class PrivacyPoliciesController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice', ['only' => ['insert', 'update', 'destroy']]);
        $this->middleware('onlyJson',['only'=>['insert','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function view()
    {
        try {
            $key = Str::of(Cache::get('key', 'home_privacy_policies:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('home_privacy_policies:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                $poli = PrivacyPolicies::get();
                $arr = [];
                foreach ($poli as $value) {
                    array_push($arr, [
                        "content"   => $value->isiPrivacy
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

    public function insert(Request $req)
    {
        $data = new PrivacyPolicies;
        try {
            $data->isiPrivacy     = $req->isiPrivacy;

            if ($data->save()) {
                Cache::forget('home_privacy_policies:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '511', 'message' => "Gagal Menyimpan Privacy Policies"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kebijakan Privasi', 'Add Kebijakan Privasi', json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        try {
            $privacy = PrivacyPolicies::where('_id', $id)->first();

            $data = array(
                'isiPrivacy'  => $req->isiPrivacy,
            );

            if ($privacy->update($data)) {
                Cache::forget('home_privacy_policies:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '238', 'message' => "Gagal Update Privacy Policies"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kebijakan Privasi', 'Update Kebijakan Privasi', json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $privacy = PrivacyPolicies::where('_id', $id)->first();
            
            if ($privacy->delete()) {
                Cache::forget('home_privacy_policies:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '620', 'message' => "Gagal Hapus Privacy Policies"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kebijakan Privasi', 'Delete Kebijakan Privasi', json_decode($response)->message));
        return $response;
    }

    public function getDataPrivacyFirstPage($take)
    {
        try {
            $results = PrivacyPolicies::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = PrivacyPolicies::count();
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

    public function getDataPrivacyByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = PrivacyPolicies::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = PrivacyPolicies::count();
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
