<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Notification;
use App\UmrohPackage;
use App\UmrohOrder;
use App\UserMobiles;
use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\InspirationController;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLogin');
        $this->middleware('onlyJson',['only'=>['getNotification','destroy']]);
    }

    public function getNotification(Request $req){
        
        try {
            $cache = Cache::remember('notification:' . date('Y-m-d') . $req->idUserMobile, env('CACHE_DURATION'), function () use ($req) {
                $count = Notification::where('idUserMobile', $req->idUserMobile)->count();
                
                $arr = [];

                if ($count > 0) {
                    $notif  = Notification::where('idUserMobile', $req->idUserMobile)->orderBy('updated_at', 'DESC')->get();

                    foreach($notif as $val){
                        if ($val->flag == 1) {
                            $umroh      = UmrohOrder::where('_id', $val['urlId'])->first();
                            array_push($arr, [
                                '_id'           => $val['urlId'],
                                'codeBooking'   => $umroh->bookingCode,
                                'position'      => $val['position'],
                                'type'          => $val['type'],
                                'title'         => $val['title'],
                                'description'   => $val['description'],
                            ]);
                        }
                        if ($val->flag == 2) {
                            array_push($arr, [
                                '_id'           => $val['urlId'],
                                'codeBooking'   => null,
                                'position'      => $val['position'],
                                'type'          => $val['type'],
                                'title'         => $val['title'],
                                'description'   => $val['description'],
                            ]);
                        }
                    }
                }

                return $arr;
            });

            if ($cache) {
                return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $cache));
            } else {
                return json_encode(array('statusCode' => '333', 'message' => "Gagal query Notifikasi"));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertUmroh($id, $idUserMobile, $description, $position)
    {
        try {
            $umroh      = UmrohOrder::where('_id', $id)->first();
            $package    = UmrohPackage::where('id', $umroh->packageId)->first();
            $count      = Notification::where('urlId', $id)->where('idUserMobile', $idUserMobile)->count();

            if ($count > 0) {
                $notif = Notification::where('urlId', $id)->where('idUserMobile', $idUserMobile)->first();

                $notif->urlId        = $umroh->_id;
                $notif->idUserMobile = $idUserMobile;
                $notif->title        = $package->name;
                $notif->description  = $description;
                $notif->position     = $position;
                $notif->type         = 1;
                $notif->flag         = 1;
                $notif->read         = 0;

                if ($notif->update()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $notif]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error update Notification']);
                }
            }else{
                $data = new Notification;

                $data->urlId        = $umroh->_id;
                $data->idUserMobile = $idUserMobile;
                $data->title        = $package->name;
                $data->description  = $description;
                $data->position     = $position;
                $data->type         = 1;
                $data->flag         = 1;
                $data->read         = 0;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertHome($id, $title, $description, $type){
        try {
            $user = UserMobiles::all();
            $arr = [];
            foreach($user as $val) { 

                array_push($arr,[
                    'urlId'        => $id,
                    'idUserMobile' => $val->_id,
                    'title'        => $title,
                    'description'  => $description,
                    'position'     => null,
                    'type'         => $type,
                    'flag'         => 2,
                    'read'         => 0,
                    'updated_at'   => date('Y-m-d h:i:s'),
                    'created_at'   => date('Y-m-d h:i:s')
                ]);
            }

            if ($data = Notification::insert($arr)) {
                Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function isRead(Request $req){
        try {
            $notif = Notification::where('urlId', $req->id)->where('idUserMobile', $req->idUserMobile)->first();
            
            $notif->read = 1;

            if ($notif->update()) {
                Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                Cache::forget('notification:' . date('Y-m-d').$req->idUserMobile);
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $notif]);
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error Update Notification']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function destroy(Request $req){

        $article    = new ArtikelController;
        $inspirasi  = new InspirationController;
        try {
            $user = Notification::where('urlId', $req->id)->where('idUserMobile', $req->idUserMobile)->where('flag', 2)->first();
            if ($user) {
                if ($user->type == 2) {
                    if($user->delete()){
                        Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                        Cache::forget('notification:' . date('Y-m-d').$req->idUserMobile);
                        return $inspirasi->viewDetail($req->id);
                    }
                } 
                if ($user->type == 3) {
                    if($user->delete()){
                        Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                        Cache::forget('notification:' . date('Y-m-d').$req->idUserMobile);
                        return $article->viewDetail($req->id);
                    }
                }
            }else{
                return json_encode(['statusCode' => '444', 'message' => 'Error get data Notification']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function destroyEvent($id){

        try {
            $user = Notification::where('_id', $id)->delete();

            if ($user > 0) {
                Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $user]);
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
}
