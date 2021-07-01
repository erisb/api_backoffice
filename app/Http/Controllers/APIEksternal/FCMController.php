<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\FirebaseTokens;
use App\SholatAlarms;

class FCMController extends Controller
{
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['sendMessageArtikel','sendMessageInspirasi']]);
        $this->middleware('onlyJson',['only'=>['getTokenWithoutLogin','getTokenWithLogin','sendMessageArtikel']]);
    }

    public function getTokenWithoutLogin(Request $req)
    {
        $imei = $req->imei;
        $token = $req->tokenFirebase;
        
        try {
            $cekDataTokenByImei = FirebaseTokens::where('imei',$imei)->first();
            
            if ($cekDataTokenByImei == '')
            {
                $dataToken = new FirebaseTokens;
                $dataToken->token = $token;
                $dataToken->imei = $imei;
                $dataToken->idUserMobile = '';
            } else {
                $dataToken = FirebaseTokens::where('imei',$imei)->first();
                $dataToken->token = $token;
            }

            if ($dataToken->save()){
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '460', 'message' => "Gagal Simpan Token Firebase"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getTokenWithLogin(Request $req)
    {
        $imei = $req->imei;
        $idUserMobile = $req->idUser;
        $token = $req->tokenFirebase;
        
        try {
            $cekDataTokenByImei = FirebaseTokens::where('imei',$imei)->first();

            if ($cekDataTokenByImei == '')
            {
                $dataToken = new FirebaseTokens;
                $dataToken->token = $token;
                $dataToken->imei = $imei;
                $dataToken->idUserMobile = $idUserMobile;
            } else {
                $cekDataTokenByIdUser = FirebaseTokens::where('idUserMobile',$idUserMobile)->first();
                if ($cekDataTokenByIdUser != '')
                {
                    $dataToken = FirebaseTokens::where('imei',$imei)->first();
                    $dataToken->token = $token;
                } else {
                    $dataToken = FirebaseTokens::where('imei',$imei)->first();
                    $dataToken->token = $token;
                    $dataToken->idUserMobile = $idUserMobile;
                }
            }

            if ($dataToken->save()){
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '460', 'message' => "Gagal Simpan Token Firebase"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function updateAlarm($imei,$tgl,$idWaktuSholat)
    {
        try{
            $cekAlarm = SholatAlarms::where('imei',$imei)->where('tglWaktuSholat',$tgl)->first();
            $listsAlarm = $cekAlarm != '' ? $cekAlarm->listWaktuSholat : [];

            foreach($listsAlarm as $key => $value){
                if ($value['idWaktuSholat'] == $idWaktuSholat)
                {
                    $listsAlarm[$key]['alarmWaktuSholat'] = 0;
                }
            }

            $dataAlarm = SholatAlarms::where('imei',$imei)->where('tglWaktuSholat',$tgl)->first();
            $dataAlarm->listWaktuSholat = $listsAlarm;
            
            if ($dataAlarm->save()){
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '462', 'message' => "Gagal Update Alarm Sholat"));
            }

        } catch(\Exception $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function sendMessageAzan(Request $req)
    {
        $imeis = $req->imei;
        $tgl = $req->tanggal;
        $idWaktuSholat = $req->idWaktu;
        
        try {
            $listTokenFirebase = [];
            foreach($imeis as $key => $value)
            {
                $cekAlarm = SholatAlarms::where('imei',$value['imei'])->where('tglWaktuSholat',$tgl)->first();
                $listsAlarm = $cekAlarm != '' ? $cekAlarm->listWaktuSholat : []; 
                foreach($listsAlarm as $valueAlarm){
                    if ($valueAlarm['idWaktuSholat'] == $idWaktuSholat && $valueAlarm['alarmWaktuSholat'] == 1)
                    {
                        $cekTokenFirebase = FirebaseTokens::where('imei',$value['imei'])->first();
                        $listTokenFirebase[$key] = $cekTokenFirebase->token;
                        $this->updateAlarm($value['imei'],$tgl,$idWaktuSholat);
                    }
                }
            }
            $namaWaktuSholat = ($idWaktuSholat == 1 ? 'Sudah Masuk Imsak' : ($idWaktuSholat == 2 ? 'Yuk Sholat Subuh' : ($idWaktuSholat == 3 ? 'Yuk Sholat Dzuhur' : ($idWaktuSholat == 4 ? 'Yuk Sholat Ashar' : ($idWaktuSholat == 5 ? 'Yuk Sholat Maghrib' : ($idWaktuSholat == 6 ? 'Yuk Sholat Isya' : ''))))));
            
            $kirim = fcm()
                        ->to($listTokenFirebase)
                        ->priority('high')
                        ->timeToLive(60*20)
                        ->notification([
                            'title' => 'Waktu Sholat',
                            'body' => $namaWaktuSholat,
                        ])
                        ->send();
                        
            if ($kirim['success'] != 0) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Berhasil', 'deskripsi' => $kirim]);
            } else {
                $response = json_encode(['statusCode' => '999', 'message' => 'Gagal Kirim', 'deskripsi' => $kirim]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response =  json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function sendMessageArtikel(Request $req)
    {
        $judul = $req->judul;

        try {
            $listTokenFirebase = [];
            $dataTokenFirebase = FirebaseTokens::all();
            foreach($dataTokenFirebase as $key => $value)
            {
                $listTokenFirebase[$key] = $value->token;
            }
            
            $kirim = fcm()
                        ->to($listTokenFirebase)
                        ->priority('high')
                        ->timeToLive(60*20)
                        ->notification([
                            'title' => 'Artikel',
                            'body' => 'Ada Artikel baru nih - '.$judul,
                        ])
                        ->send();
            
            if ($kirim['success'] != 0) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Berhasil', 'deskripsi' => $kirim]);
            } else {
                $response = json_encode(['statusCode' => '999', 'message' => 'Gagal Kirim', 'deskripsi' => $kirim]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response =  json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function sendMessageInspirasi()
    {
        try {
            $listTokenFirebase = [];
            $dataTokenFirebase = FirebaseTokens::all();
            foreach($dataTokenFirebase as $key => $value)
            {
                $listTokenFirebase[$key] = $value->token;
            }
            
            $kirim = fcm()
                        ->to($listTokenFirebase)
                        ->priority('high')
                        ->timeToLive(60*20)
                        ->notification([
                            'title' => 'Inspirasi',
                            'body' => 'Ada Inspirasi baru nih',
                        ])
                        ->send();
            
            if ($kirim['success'] != 0) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Berhasil', 'deskripsi' => $kirim]);
            } else {
                $response = json_encode(['statusCode' => '999', 'message' => 'Gagal Kirim', 'deskripsi' => $kirim]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response =  json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function sendMessageJumatan($time)
    {

        try {
            $listTokenFirebase = [];
            $dataTokenFirebase = FirebaseTokens::all();
            foreach($dataTokenFirebase as $key => $value)
            {
                $listTokenFirebase[$key] = $value->token;
            }
            
            $kirim = fcm()
                        ->to($listTokenFirebase)
                        ->priority('high')
                        ->timeToLive(60*20)
                        ->notification([
                            'title' => "Sholat Jum'at Yuk!",
                            'body' => "lupakan aktifitas sejenak, sekarang waktunya menghadap Qiblat, yuk shalat jum'at",
                        ])
                        ->send();
            
            if ($kirim['success'] != 0) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Berhasil', 'deskripsi' => $kirim]);
            } else {
                $response = json_encode(['statusCode' => '999', 'message' => 'Gagal Kirim', 'deskripsi' => $kirim]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response =  json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

}
