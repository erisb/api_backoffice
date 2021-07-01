<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\Inspirations;
use App\Categories;
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
        $this->middleware('authLoginBackOffice',['only' => ['insert','update','destroy']]);
        $this->middleware('onlyJson',['only'=>['getDataInspirationBySearch','getDataInspirationBySearch']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }
    
    public function getInspirasi()
    {
        try {
            $key = Str::of(Cache::get('key', 'inspirasi:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('inspirasi:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return Inspirations::where('statusInspiration', '1')->get();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Data Kosong', 'data' => null]);
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
        $notif = new NotificationController;

        $validatorGambar = Validator::make($req->all(), Inspirations::$rulesGambarInspirasi, Inspirations::$messages);
        $validatorFormat = Validator::make($req->all(), Inspirations::$rulesFormatInspirasi, Inspirations::$messages);
        $validatorMax    = Validator::make($req->all(), Inspirations::$rulesMaxInspirasi, Inspirations::$messages);

        try {
            if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            }
            if ($req->hasFile('imageInspiration')) {

                $files = $req->file('imageInspiration'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/gambar_inspirasi/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $data = new Inspirations;

            $data->categoryId           = $req->categoryId;
            $data->contentInspiration   = $req->contentInspiration;
            $data->sourceInspiration    = $req->sourceInspiration;
            $data->statusInspiration    = $req->statusInspiration;
            $data->imageInspiration     = env('OSS_DOMAIN') . $filePath;
            $data->meaningInspiration   = $req->meaningInspiration;
            if ($data->save()) {
                $type = 2;
                $notif->insertHome($data->_id, $data->sourceInspiration, $data->contentInspiration, $type);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Add Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        $validatorGambar = Validator::make($req->all(), Inspirations::$rulesGambarInspirasi, Inspirations::$messages);
        $validatorFormat = Validator::make($req->all(), Inspirations::$rulesFormatInspirasi, Inspirations::$messages);
        $validatorMax    = Validator::make($req->all(), Inspirations::$rulesMaxInspirasi, Inspirations::$messages);

        try {
            $data = Inspirations::where('_id', $id)->first();
            
            if ($req->imageInspiration != 'null')
            {
                if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($data->imageInspiration != "") {
                    $ex = explode("/", $data->imageInspiration);
                    Storage::disk('oss')->delete('/gambar_inspirasi/' . $ex[4]);
                }

                if ($req->hasFile('imageInspiration')) {

                    $files = $req->file('imageInspiration'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gambar_inspirasi/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data->categoryId           = $req->categoryId;
                $data->contentInspiration   = $req->contentInspiration;
                $data->sourceInspiration    = $req->sourceInspiration;
                $data->statusInspiration    = $req->statusInspiration;
                $data->imageInspiration     = env('OSS_DOMAIN') . $filePath;
                $data->meaningInspiration   = $req->meaningInspiration;
            } else {
                $data->categoryId           = $req->categoryId;
                $data->contentInspiration   = $req->contentInspiration;
                $data->sourceInspiration    = $req->sourceInspiration;
                $data->statusInspiration    = $req->statusInspiration;
                $data->meaningInspiration   = $req->meaningInspiration;
            }

            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Update Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        $data = Inspirations::where('_id', $id)->first();
        try {
            if ($data->imageInspiration != "") {
                $ex = explode("/", $data->imageInspiration);
                Storage::disk('oss')->delete('/gambar_inspirasi/' . $ex[4]);
            }
            if ($data->delete()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Inspirasi"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Delete Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function getDataInspirationFirstPage($take)
    {
        try {
            $results = Inspirations::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'contentInspiration'    => $datas->contentInspiration,
                    'sourceInspiration'     => $datas->sourceInspiration,
                    'statusInspiration'     => $datas->statusInspiration,
                    'imageInspiration'      => $datas->imageInspiration,
                    'meaningInspiration'    => $datas->meaningInspiration,
                ];
            }
            $totalData = Inspirations::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults, 'total' => $totalData]);
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

    public function getDataInspirationByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = Inspirations::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'contentInspiration'    => $datas->contentInspiration,
                    'sourceInspiration'     => $datas->sourceInspiration,
                    'statusInspiration'     => $datas->statusInspiration,
                    'imageInspiration'      => $datas->imageInspiration,
                    'meaningInspiration'    => $datas->meaningInspiration,
                ];
            }
            $totalData = Inspirations::count();
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
            $results = Inspirations::where('contentInspiration', 'like', '%' . $val . '%')->orWhere('sourceInspiration', 'like', '%' . $val . '%')->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'contentInspiration'    => $datas->contentInspiration,
                    'sourceInspiration'     => $datas->sourceInspiration,
                    'statusInspiration'     => $datas->statusInspiration,
                    'imageInspiration'      => $datas->imageInspiration,
                    'meaningInspiration'    => $datas->meaningInspiration,
                ];
            }

            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults]);
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

    public function viewDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'detail_inspirasi:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('detail_inspirasi:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                return Inspirations::where('_id', $id)->first();
            });
            if ($cache) {
                $arr = [];
                $data = Inspirations::select('totalViewer')->where('_id', $id)->first();
                if ($data->totalViewer != "") {
                    $data->totalViewer = $data->totalViewer + 1;
                } else {
                    $data->totalViewer = 1;
                }
                $data->save();
                array_push($arr, ['_id' => $cache->_id, 'categoryId' => $cache->categoryId, 'contentInspiration' => $cache->contentInspiration, 'sourceInspiration' => $cache->sourceInspiration, 'imageInspiration' => $cache->imageInspiration, 'statusInspiration' => $cache->statusInspiration, 'totalViewer' => $data->totalViewer]);
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
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
}
