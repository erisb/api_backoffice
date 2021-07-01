<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\Trendings;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\NotificationController;

class InspirationController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLoginBackOffice',['only' => ['insert','update','destroy']]);
        // $this->middleware('onlyJson',['only'=>['getDataInspirationBySearch','getDataInspirationBySearch']]);
        // $this->token = request()->token;
        // $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        // $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        // $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }
    
    public function homeTrending()
    {
        try {
            $key = Str::of(Cache::get('key', 'trending:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('trending:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return Trendings::where('publishTrending', '1')->orderBy('created_at', 'DESC')->get();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '288','message' => 'Error get Data Trending']);
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
        // $notif = new NotificationController;

        $validatorGambar = Validator::make($req->all(), Trendings::$rulesGambarTrending, Trendings::$messages);
        $validatorFormat = Validator::make($req->all(), Trendings::$rulesFormatTrending, Trendings::$messages);
        $validatorMax    = Validator::make($req->all(), Trendings::$rulesMaxTrending, Trendings::$messages);

        try {
            if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            }
            if ($req->hasFile('imageTrending')) {

                $files = $req->file('imageTrending'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/gambar_inspirasi/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $data = new Trendings;

            $data->categoryId           = $req->categoryId;
            $data->titleTrending        = $req->titleTrending;
            $data->contentTrending      = $req->contentTrending;
            $data->imageTrending        = env('OSS_DOMAIN') . $filePath;
            $data->publishTrending      = $req->publishTrending;
            if ($data->save()) {
                // $type = 2;
                // $notif->insertHome($data->_id, $data->sourceInspiration, $data->contentInspiration, $type);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Trending"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Trending','Add Trending',json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        $validatorGambar = Validator::make($req->all(), Trendings::$rulesGambarTrending, Trendings::$messages);
        $validatorFormat = Validator::make($req->all(), Trendings::$rulesFormatTrending, Trendings::$messages);
        $validatorMax    = Validator::make($req->all(), Trendings::$rulesMaxTrending, Trendings::$messages);

        try {
            $data = Trendings::where('_id', $id)->first();
            
            if ($req->imageTrending != 'null')
            {
                if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($data->imageTrending != "") {
                    $ex = explode("/", $data->imageTrending);
                    Storage::disk('oss')->delete('/gambar_inspirasi/' . $ex[4]);
                }

                if ($req->hasFile('imageTrending')) {

                    $files = $req->file('imageTrending'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gambar_inspirasi/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                
                $data->categoryId           = $req->categoryId;
                $data->titleTrending        = $req->titleTrending;
                $data->contentTrending      = $req->contentTrending;
                $data->imageTrending        = env('OSS_DOMAIN') . $filePath;
                $data->publishTrending      = $req->publishTrending;
            } else {
                $data->categoryId           = $req->categoryId;
                $data->titleTrending        = $req->titleTrending;
                $data->contentTrending      = $req->contentTrending;
                $data->publishTrending      = $req->publishTrending;
            }

            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Trending"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Trending','Update Trending',json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        $data = Trendings::where('_id', $id)->first();
        try {
            if ($data->imageTrending != "") {
                $ex = explode("/", $data->imageTrending);
                Storage::disk('oss')->delete('/gambar_inspirasi/' . $ex[4]);
            }
            if ($data->delete()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Trending"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Trending','Delete Trending',json_decode($response)->message));
        return $response;
    }

    public function getDataInspirationFirstPage($take)
    {
        try {
            $results = Trendings::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id'               => $datas->_id,
                    'categoryId'        => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'titleTrending'     => $datas->titleTrending,
                    'contentTrending'   => $datas->contentTrending,
                    'imageTrending'     => $datas->imageTrending,
                    'publishTrending'   => $datas->publishTrending,
                ];
            }
            $totalData = Trendings::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults, 'total' => $totalData]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal Get data Trending']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataInspirationByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = Trendings::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id'               => $datas->_id,
                    'categoryId'        => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'titleTrending'     => $datas->titleTrending,
                    'contentTrending'   => $datas->contentTrending,
                    'imageTrending'     => $datas->imageTrending,
                    'publishTrending'   => $datas->publishTrending,
                ];
            }
            $totalData = Trendings::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults, 'total' => $totalData]);
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

    public function getDataInspirationBySearch(Request $req)
    {
        $val = str_replace(' ', '', $req->search);
        try {
            $results = Trendings::where('contentTrending', 'like', '%' . $val . '%')->orWhere('titleTrending', 'like', '%' . $val . '%')->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id'               => $datas->_id,
                    'categoryId'        => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'titleTrending'     => $datas->titleTrending,
                    'contentTrending'   => $datas->contentTrending,
                    'imageTrending'     => $datas->imageTrending,
                    'publishTrending'   => $datas->publishTrending,
                ];
            }

            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal get Data Trending', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function viewDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'detail_trending:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('detail_trending:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                return Trendings::where('_id', $id)->first();
            });
            if ($cache) {
                $arr = [];
                $data = Trendings::select('totalViewerTrending')->where('_id', $id)->first();
                if ($data->totalViewerTrending != "") {
                    $data->totalViewerTrending = $data->totalViewerTrending + 1;
                } else {
                    $data->totalViewerTrending = 1;
                }
                $data->save();
                array_push($arr, [
                    '_id'               => $cache->_id, 
                    'categoryId'        => $cache->categoryId, 
                    'titleTrending'     => $cache->titleTrending,
                    'contentTrending'   => $cache->contentTrending,
                    'imageTrending'     => $cache->imageTrending,
                    'publishTrending'   => $cache->publishTrending,
                    ]);
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal get Detail Trending', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
}
