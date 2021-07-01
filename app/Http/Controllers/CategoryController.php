<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Categories;
use App\Events\CacheFlushEvent;
use App\Http\Controllers\APIEksternal\PergiUmrohController;
use App\Events\BackOfficeUserLogEvent;
use App\Helpers\FormatDate;

class CategoryController extends Controller
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
        $this->middleware('onlyJson',['only'=>['getDataCategoryBySearch','update','insert']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function insert(Request $req)
    {
        try {
            $data = new Categories;
            $data->categoryName =  $req->categoryName;
            $data->statusCategory =  $req->statusCategory;

            if ($data->save()) {
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
        Cache::forget('categories');
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kategori','Add Kategori - '.$req->categoryName,json_decode($response)->message));
        return $response;
    }

    public function getCategory()
    {
        try {
            $key = Str::of(Cache::get('key', 'categories:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $umroh      = new PergiUmrohController;

            $cache = Cache::remember('categories:' . date('Y-m-d'), 3600, function () use ($umroh) {
                $category = Categories::where('statusCategory', '1')->orderBy('created_at', 'DESC')->get();
                // $dataUmroh  = $umroh->packageHome();
                // $arr_umroh = [];
                // if ($dataUmroh != null) {
                //     foreach (json_decode($dataUmroh) as $val) {
                //         array_push($arr_umroh, [
                //             "_id"                   => $val->id,
                //             "updated_at"            => "",
                //             "created_at"            => "",
                //             'titleItemCategory'     => $val->name,
                //             'contentItemCategory'   => "",
                //             'imageItemCategory'     => $val->image,
                //             'meaning'               => "",
                //             "dateItemCategory"      => $val->departure_date,
                //             "priceItemCategory"     => $val->original_price,
                //             'categoryId'            => "",
                //         ]);
                //     }
                // }
                $arr = [];
                foreach ($category as $value) {
                    if ($value->categoryName == 'Artikel') {
                        array_push($arr, [
                            '_id'           => $value->_id,
                            'categoryName'  => $value->categoryName,
                            'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                            'created_at'    => FormatDate::stringToDate(($value->created_at)),
                            'data'          => $value->artikel(),
                        ]);
                    } else if ($value->categoryName == 'Inspirasi') {
                        array_push($arr, [
                            '_id'           => $value->_id,
                            'categoryName'  => $value->categoryName,
                            'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                            'created_at'    => FormatDate::stringToDate(($value->created_at)),
                            'data'          => $value->inspirasi(),
                        ]);
                    }
                    //  else if ($value->categoryName == 'Umroh/Haji') {
                    //     array_push($arr, [
                    //         '_id'           => $value->_id,
                    //         'categoryName'  => $value->categoryName,
                    //         'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                    //         'created_at'    => FormatDate::stringToDate(($value->created_at)),
                    //         'data'          => $arr_umroh,
                    //     ]);
                    // }
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

    public function searchCategory(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'categories:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $umroh      = new PergiUmrohController;

            $cache = Cache::remember('categories:' . date('Y-m-d'), 3600, function () use ($umroh, $req) {
                $category = Categories::where('statusCategory', '1')->orderBy('created_at', 'DESC')->get();
                
                $arr_umroh = [];
                $arr = [];
                foreach ($category as $value) {
                    if ($value->categoryName == $req->search && $req->search == "Artikel") {
                        array_push($arr, [
                            '_id'           => $value->_id,
                            'categoryName'  => $value->categoryName,
                            'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                            'created_at'    => FormatDate::stringToDate(($value->created_at)),
                            'data'          => $value->searchArtikel($req->q),
                        ]);
                    } else if ($value->categoryName == $req->search && $req->search == "Inspirasi") {
                        array_push($arr, [
                            '_id'           => $value->_id,
                            'categoryName'  => $value->categoryName,
                            'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                            'created_at'    => FormatDate::stringToDate(($value->created_at)),
                            'data'          => $value->searchInspirasi($req->q),
                        ]);
                    } else if ($value->categoryName == $req->search && $req->search == "Umroh/Haji") {
                        array_push($arr, [
                            '_id'           => $value->_id,
                            'categoryName'  => $value->categoryName,
                            'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                            'created_at'    => FormatDate::stringToDate(($value->created_at)),
                            'data'          => $value->searchUmroh($req->q),
                        ]);
                    } else if($req->search == "") {
                        if ($value->categoryName == 'Artikel') {
                            array_push($arr, [
                                '_id'           => $value->_id,
                                'categoryName'  => $value->categoryName,
                                'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                                'created_at'    => FormatDate::stringToDate(($value->created_at)),
                                'data'          => $value->searchArtikel($req->q),
                            ]);
                        } else if ($value->categoryName == 'Inspirasi') {
                            array_push($arr, [
                                '_id'           => $value->_id,
                                'categoryName'  => $value->categoryName,
                                'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                                'created_at'    => FormatDate::stringToDate(($value->created_at)),
                                'data'          => $value->searchInspirasi($req->q),
                            ]);
                        } else if ($value->categoryName == 'Umroh/Haji') {
                            array_push($arr, [
                                '_id'           => $value->_id,
                                'categoryName'  => $value->categoryName,
                                'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                                'created_at'    => FormatDate::stringToDate(($value->created_at)),
                                'data'          => $value->searchUmroh($req->q),
                            ]);
                        }
                    }
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


    public function update(Request $req, $id)
    {
        try {
            $data = Categories::where('_id', $id)->first();
            $data->categoryName =  $req->categoryName;
            $data->statusCategory =  $req->statusCategory;
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
        Cache::forget('categories');
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kategori','Update Kategori - '.$req->categoryName,json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = Categories::where('_id', $id)->first();
            $namaKategori = $data->categoryName;
            
            if ($data->delete()) {
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
        Cache::forget('categories');
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Kategori','Delete Kategori - '.$namaKategori,json_decode($response)->message));
        return $response;
    }

    public function getDataCategoryFirstPage($take)
    {
        try {
            $results = Categories::skip(0)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = Categories::count();
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

    public function getDataCategoryByPage($take,$page)
    {
        $skip = ($take*$page)-$take;
        try {
            $results = Categories::skip($skip)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = Categories::count();
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

    public function getDataCategoryBySearch(Request $req)
    {
        $val = str_replace(' ','',$req->search);
        try {
            $result = Categories::where('categoryName','like','%'.$val.'%')->orderBy('_id','desc')->get();
            if ($result) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result]);
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

    public function getDataCategory()
    {
        try {
            $result = Categories::where('statusCategory','1')->orderBy('_id','desc')->get();
            if ($result) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result]);
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
