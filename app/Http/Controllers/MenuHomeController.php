<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\MenuHomes;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;
use App\Helpers\FormatDate;

class MenuHomeController extends Controller
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
        $this->middleware('onlyJson',['only'=>['getDataMenuMobileBySearch']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    // Menampilkan semua MenuHome
    public function getMenuHome()
    {
        try {
            $key = Str::of(Cache::get('key', 'MenuHomes:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('MenuHomes:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                $menu = MenuHomes::where('statusMenu', '1')->orderBy('created_at', 'DESC')->get();
                $arr = [];
                foreach ($menu as $value) {
                    array_push($arr, [
                        "_id"           => $value->_id,
                        "idMenu"        => $value->idMenu,
                        "menuTitle"     => $value->judulMenu,
                        "menuImage"     => $value->gambarMenu,
                        "updated_at"    => FormatDate::stringToDate((string)$value->updated_at),
                        "created_at"    => FormatDate::stringToDate((string)$value->created_at),
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

    // Menampilkan Detail MenuHome
    public function viewDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'detail_menuHome:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('detail_menuHome:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                return MenuHomes::where('_id', $id)->first();
            });
            if ($cache) {
                return response()->json(['message' => 'Sukses', 'data' => $cache]);
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
        Cache::forget('menuHome:' . date('Y-m-d'));
        Cache::forget('MenuHomesComplain:' . date('Y-m-d'));

        $validatorGambar = Validator::make($req->all(), MenuHomes::$rulesgambarMenu, MenuHomes::$messages);
        $validatorFormat = Validator::make($req->all(), MenuHomes::$rulesFormatMenu, MenuHomes::$messages);
        $validatorMax    = Validator::make($req->all(), MenuHomes::$rulesMaxMenu, MenuHomes::$messages);

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
            // $response = json_encode(['statusCode' => '000', 'message' => "Sukses", 'data'=>$req->file('gambarMenu')]);
            if ($req->hasFile('gambarMenu')) {

                $files = $req->file('gambarMenu'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/menu_home/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $data = new MenuHomes;
            $data->idMenu       =  $req->idMenu;
            $data->judulMenu    =  $req->judulMenu;
            $data->statusMenu   =  $req->statusMenu;
            $data->roleMenu     =  $req->roleMenu;
            $data->gambarMenu   =  env('OSS_DOMAIN') . $filePath;
            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '483', 'message' => "Gagal Menyimpan Menu Home"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Menu Home','Add Menu Home - '.$req->judulMenu,json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        Cache::forget('menuHome:' . date('Y-m-d'));
        Cache::forget('detail_menuHome:' . date('Y-m-d') . ':' . $id);
        Cache::forget('MenuHomesComplain:' . date('Y-m-d'));
        $validatorGambar = Validator::make($req->all(), MenuHomes::$rulesgambarMenu, MenuHomes::$messages);
        $validatorFormat = Validator::make($req->all(), MenuHomes::$rulesFormatMenu, MenuHomes::$messages);
        $validatorMax    = Validator::make($req->all(), MenuHomes::$rulesMaxMenu, MenuHomes::$messages);
    
        try {
            $menu = MenuHomes::where('_id', $id)->first();

            if ($req->gambarMenu != 'null') {
                if ($validatorGambar->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorGambar->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage

                if ($menu->gambarMenu != "") {
                    $data = explode("/", $menu->gambarMenu);
                    Storage::disk('oss')->delete('/menu_home/' . $data[4]);
                }

                if ($req->hasFile('gambarMenu')) {

                    $files = $req->file('gambarMenu'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/menu_home/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data = array(
                    'judulMenu'     => $req->judulMenu,
                    'statusMenu'    => $req->statusMenu,
                    'roleMenu'      => $req->roleMenu,
                    'gambarMenu'    => env('OSS_DOMAIN') . $filePath
                );
            } else {
                $data = array(
                    'judulMenu'     => $req->judulMenu,
                    'statusMenu'    => $req->statusMenu,
                    'roleMenu'      => $req->roleMenu,
                );
            }

            if ($menu->update($data)) {
                // Cache::forget('detail_article:' . $id);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '246', 'message' => "Gagal Update Menu Home"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Menu Home','Update Menu Home - '.$req->judulMenu,json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        Cache::forget('menuHome:' . date('Y-m-d'));
        Cache::forget('detail_menuHome:' . date('Y-m-d') . ':' . $id);
        Cache::forget('MenuHomesComplain:' . date('Y-m-d'));
        
        try {
            $menu = MenuHomes::where('_id', $id)->first();
            $judulMenu = $menu->judulMenu;

            //delete image for storage
            $data = explode("/", $menu->gambarMenu);
            if (Storage::disk('oss')->delete('/menu_home/' . $data[4])) {
                if ($menu->delete()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '670', 'message' => "Gagal Hapus Menu Home"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Menu Home','Delete Menu Home - '.$judulMenu,json_decode($response)->message));
        return $response;
    }

    public function getDataMenuMobileFirstPage($take)
    {
        try {
            $menus = MenuHomes::skip(0)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = MenuHomes::count();
            if ($menus) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $menus, 'total' => $totalData]);
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

    public function getDataMenuMobileByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $menus = MenuHomes::skip($skip)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = MenuHomes::count();
            if ($menus) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $menus, 'total' => $totalData]);
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

    public function getDataMenuMobileBySearch(Request $req)
    {
        $val = str_replace(' ', '', $req->search);
        
        try {
            $result = MenuHomes::where('judulMenu', 'like', '%' . $val . '%')->orderBy('_id','desc')->get();
            
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

    public function getMenuHomeForComplain()
    {
        try {
            $key = Str::of(Cache::get('key', 'MenuHomesComplain:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('MenuHomesComplain:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                $menu = MenuHomes::where('statusMenu', '1')->orderBy('idMenu', 'ASC')->get();
                $arr = [];
                foreach ($menu as $value) {
                    array_push($arr, [
                        "_id"           => $value->_id,
                        "idMenu"        => $value->idMenu,
                        "menuTitle"     => $value->judulMenu,
                        "menuImage"     => $value->gambarMenu,
                        "updated_at"    => FormatDate::stringToDate((string)$value->updated_at),
                        "created_at"    => FormatDate::stringToDate((string)$value->created_at),
                    ]);
                }
                usort($arr, function($a, $b) {
                    return $a['idMenu'] <=> $b['idMenu'];
                });
                $out = array_splice($arr, 7, 1);
                array_push($arr,$out[0]);
                return $arr;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '333', 'message' => 'Data Kosong', 'data' => $cache]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
}
