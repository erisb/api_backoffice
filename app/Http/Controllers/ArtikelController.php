<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Articles;
use App\Categories;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\NotificationController;

class ArtikelController extends Controller
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
        $this->middleware('onlyJson',['only'=>['getDataArtikelBySearch','viewAll','getArtikel','getDataArtikelBySearch']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function search(Request $req)
    {
        $data = Articles::when($req->q, function ($data) use ($req) {
            $data->where('articleContent', 'LIKE', '%' . $req->q . '%')
                ->orWhere('articleTitle', 'LIKE', '%' . $req->q . '%');
        })->orderBy('created_at', 'DESC')->take(6)->get();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // Menampilkan semua artikel
    public function getArtikel(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'articles:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('articles:' . date('Y-m-d'), env('CACHE_DURATION'), function ()  use ($req) {
                return json_decode(Articles::where('categoryId', $req->categoryId)
                    ->where('publish', '1')->orderBy('created_at', 'DESC')->get());
            });
            if ($cache) {
                $arr = [];
                foreach ($cache as $value) {
                    array_push($arr, ['_id' => $value->_id, 'idCategory' => $value->categoryId, 'articleTitle' => $value->articleTitle, 'articleContent' => $value->articleContent, 'articleImage' => $value->articleImage, 'articleAdmin' => $value->articleAdmin]);
                }
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

    // Menampilkan Detail Artikel
    public function viewDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'detail_article:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('detail_article:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                return Articles::where('_id', $id)->first();
            });
            if ($cache) {
                $arr = [];
                $data = Articles::select('totalViewer')->where('_id', $id)->first();
                if ($data->totalViewer != "") {
                    $data->totalViewer = $data->totalViewer + 1;
                } else {
                    $data->totalViewer = 1;
                }
                $data->save();
                array_push($arr, ['_id' => $cache->_id, 'categoryId' => $cache->categoryId, 'articleTitle' => $cache->articleTitle, 'articleContent' => $cache->articleContent, 'articleImage' => $cache->articleImage, 'articleAdmin' => $cache->articleAdmin, 'totalViewer' => $data->totalViewer]);
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

    public function viewAll(Request $req)
    {
        try {
            $data = Articles::where('categoryId', $req->idKategori)->where('publish', '1')->orderBy('created_at', 'DESC')->get();
            return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'article' => $data));
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    // Tampilan 4 artikel pada home
    public function view()
    {
        try {
            $key = Str::of(Cache::get('key', 'home_articles:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('home_articles:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return Articles::select('articleTitle', 'articleImage')->where('publish', '1')->orderBy('created_at', 'DESC')->take(4)->get();
            });
            if ($cache) {
                $arr = [];
                foreach ($cache as $value) {
                    array_push($arr, ['articleTitle' => $value->articleTitle, 'articleImage' => $value->articleImage]);
                }
                return $arr;
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
        $notif = new NotificationController;

        $validatorGambar = Validator::make($req->all(), Articles::$rulesGambarArtikel, Articles::$messages);
        $validatorFormat = Validator::make($req->all(), Articles::$rulesFormatArtikel, Articles::$messages);
        $validatorMax    = Validator::make($req->all(), Articles::$rulesMaxArtikel, Articles::$messages);

        try {

            if ($validatorGambar->fails()) {
                $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            }

            // $width = 600; // your max width
            // $height = 600; // your max height

            if ($req->hasFile('articleImage')) {

                $files = $req->file('articleImage'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/gambar_artikel/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $data = new Articles;

            $data->categoryId       = $req->categoryId;
            $data->articleTitle     = $req->articleTitle;
            $data->articleContent   = $req->articleContent;
            $data->articleImage     = env('OSS_DOMAIN') . $filePath;
            $data->articleAdmin     = $req->articleAdmin;
            $data->publish          = $req->publish;

            if ($data->save()) {
                Cache::forget('articles:' . date('Y-m-d'));
                Cache::forget('home_articles:' . date('Y-m-d'));
                $type = 3;
                $notif->insertHome($data->_id, $data->articleTitle, $data->articleContent, $type);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'artikel' => $data));
            } else {
                $response = json_encode(array('statusCode' => '251', 'message' => "Gagal Menyimpan Artikel"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Artikel','Add Artikel - '.$req->articleTitle,json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        $validatorGambar = Validator::make($req->all(), Articles::$rulesGambarArtikel, Articles::$messages);
        $validatorFormat = Validator::make($req->all(), Articles::$rulesFormatArtikel, Articles::$messages);
        $validatorMax    = Validator::make($req->all(), Articles::$rulesMaxArtikel, Articles::$messages);

        $artikel = Articles::where('_id', $id)->first();
        try {
            if ($req->articleImage != 'null')
            {
                if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($artikel->articleImage != null) {
                    $data = explode("/", $artikel->articleImage);
                    Storage::disk('oss')->delete('/gambar_artikel/' . $data[4]);
                }

                //save gambar to storage

                if ($req->hasFile('articleImage')) {

                    $files = $req->file('articleImage'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gambar_artikel/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data = array(
                    'categoryId'        => $req->categoryId,
                    'articleTitle'      => $req->articleTitle,
                    'articleContent'    => $req->articleContent,
                    'articleImage'      => env('OSS_DOMAIN') . $filePath,
                    'articleAdmin'      => $req->articleAdmin,
                    'publish'           => $req->publish,
                );
            } else {
                $data = array(
                    'categoryId'        => $req->categoryId,
                    'articleTitle'      => $req->articleTitle,
                    'articleContent'    => $req->articleContent,
                    'articleAdmin'      => $req->articleAdmin,
                    'publish'           => $req->publish,
                );
            }

            if ($artikel->update($data)) {
                Cache::forget('articles:' . date('Y-m-d'));
                Cache::forget('home_articles:' . date('Y-m-d'));
                Cache::forget('detail_article:' . date('Y-m-d') . ':' . $id);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '238', 'message' => "Gagal Update Artikel"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Artikel','Update Artikel - '.$req->articleTitle,json_decode($response)->message));
        return $response;
    }


    public function destroy($id)
    {
        $artikel = Articles::where('_id', $id)->first();
        $judulArtikel = $artikel->articleTitle;
        try {
            //delete image for storage
            $data = explode("/", $artikel->articleImage);
            if (Storage::disk('oss')->delete('/gambar_artikel/' . $data[4])) {
                if ($artikel->delete()) {
                    Cache::forget('articles:' . date('Y-m-d'));
                    Cache::forget('home_articles:' . date('Y-m-d'));
                    Cache::forget('detail_article:' . date('Y-m-d') . ':' . $id);
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '620', 'message' => "Gagal Hapus Artikel"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Artikel','Delete Artikel - '.$judulArtikel,json_decode($response)->message));
        return $response;
    }

    public function getDataArtikelFirstPage($take)
    {
        try {
            $results = Articles::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => $datas->categoryId != null ? Categories::where('_id', $datas->categoryId)->first(['categoryName']) : null,
                    'articleTitle' => $datas->articleTitle,
                    'articleContent' => $datas->articleContent,
                    'articleImage' => $datas->articleImage,
                    'publish' => $datas->publish,
                    'articleAdmin' => $datas->articleAdmin,
                ];
            }
            $totalData = Articles::count();
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

    public function getDataArtikelByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = Articles::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'articleTitle' => $datas->articleTitle,
                    'articleContent' => $datas->articleContent,
                    'articleImage' => $datas->articleImage,
                    'publish' => $datas->publish,
                    'articleAdmin' => $datas->articleAdmin,
                ];
            }
            $totalData = Articles::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults, 'total' => $totalData]);
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

    public function getDataArtikelBySearch(Request $req)
    {
        $val = str_replace(' ', '', $req->search);
        try {
            $results = Articles::where('articleTitle', 'like', '%' . $val . '%')->orWhere('articleContent', 'like', '%' . $val . '%')->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $datas) {
                $newResults[] = [
                    '_id' => $datas->_id,
                    'categoryId' => Categories::where('_id', $datas->categoryId)->first(['categoryName']),
                    'articleTitle' => $datas->articleTitle,
                    'articleContent' => $datas->articleContent,
                    'articleImage' => $datas->articleImage,
                    'publish' => $datas->publish,
                    'articleAdmin' => $datas->articleAdmin,
                ];
            }
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $newResults]);
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

    public function getAllDataArtikel()
    {
        try {
            $results = Articles::where('publish','1')->orderBy('_id', 'desc')->get();
            $newResults = [];
            foreach ($results as $key => $datas) {
                $newResults[] = Categories::where('_id', $datas->categoryId)->first(['categoryName']);
            }
            $newResults = array_unique($newResults);

            $arrBaru = [];
            foreach ($newResults as $key => $datas) {
                $arrBaru[] = [
                    "category" => $newResults[$key],
                    "artikel_utama" => Articles::where('categoryId',$newResults[$key]['_id'])->orderBy('_id', 'desc')->limit(1)->first(),
                    "artikel_recent" => Articles::where('categoryId',$newResults[$key]['_id'])->orderBy('_id', 'desc')->skip(1)->get(),
                ];
            }

            $totalData = Articles::count();
            $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arrBaru, 'total' => $totalData]);
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDetilArtikel($id)
    {
        try {
            $results = Articles::where(['_id'=>$id,'publish'=>'1'])->first();
            $newResults = [];
            if ($results != null) {
                $newResults[] = [
                    '_id' => $results->_id,
                    'categoryId' => $results->categoryId != null ? Categories::where('_id', $results->categoryId)->first(['categoryName']) : null,
                    'articleTitle' => $results->articleTitle,
                    'articleContent' => $results->articleContent,
                    'articleImage' => $results->articleImage,
                    'articleAdmin' => $results->articleAdmin,
                    'totalViewer' => $results->totalViewer
                ];

                $viewerBefore = Articles::where(['_id'=>$id,'publish'=>'1'])->first(['totalViewer'])->totalViewer ? Articles::where(['_id'=>$id,'publish'=>'1'])->first(['totalViewer'])->totalViewer : 0;
                $totalViewer = $viewerBefore + 1;
                Articles::where(['_id'=>$id,'publish'=>'1'])->update(['totalViewer'=>$totalViewer]);
            } else {
                $newResults;
            }
            
            $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => count($newResults) !== 0 ? $newResults[0] : $newResults]);
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
