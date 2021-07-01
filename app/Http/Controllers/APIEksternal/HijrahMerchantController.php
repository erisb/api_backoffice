<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;
use App\MerchantToken;
use App\MerchantTransaction;
use Illuminate\Http\Response;
use App\Http\Controllers\APIEksternal\FCMController;

class HijrahMerchantController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['lokasi']]);
    }

    public function loginForAuth()
    {
        try{
            $body = json_encode([
                "client_id"     => env('CLIENT_ID_MERCHANT'),
                "client_secret" => env('CLIENT_SECRET_MERCHANT')
            ]);
            
            $send = [
                'headers'   => ['Content-Type'  => 'application/json'],
                'body'      => $body
            ];
            
            $key = Str::of(Cache::get('key','merchantToken:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            
            $client = new Client();
            $result = Cache::remember('merchantToken:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->post(env('API_MERCHANT').'login/app_hijrah', $send)->getBody()->getContents());
            });
            if ($result) {
                MerchantToken::truncate();
                $merchant = new MerchantToken;

                $merchant->token_type   = $result->token_type;
                $merchant->access_token = $result->access_token;
                $merchant->expires_in   = $result->expires_in;
                
                if ($merchant->save()) {
                    $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>$merchant]);
                }else {
                    $response = json_encode(['statusCode'=>'333', 'message'=>'Gagal save data']);
                }
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function refreshAuth()
    {
        try{
            $key = Str::of(Cache::get('key','merchantTokenRefresh:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $data = MerchantToken::first();
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
                ];
            
            $send = [
                'http_errors' => false,
                'headers'   => $headers
            ];
            $client = new Client();
            $result = Cache::remember('merchantTokenRefresh:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->post(env('API_MERCHANT').'refresh/app_hijrah', $send)->getBody()->getContents());
            });
            if ($result) {

                $data->token_type   = $result->token_type;
                $data->access_token = $result->access_token;
                $data->expires_in   = $result->expires_in;
                
                if ($data->save()) {
                    $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>$data]);
                }else {
                    $response = json_encode(['statusCode'=>'333', 'message'=>'Gagal save data']);
                }
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function masjid(Request $req)
    {
        try{
            $key = Str::of(Cache::get('key','merchant:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $body = json_encode([
                'radius' => '3',
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
            ]);
            $send = [
                'headers'   => $headers,
                'body'      => $body,
            ];
            $client = new Client();
            $result = Cache::remember('merchant:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->post(env('API_MERCHANT').'tes_masjid_radius', $send)->getBody()->getContents());
            });
            if ($result->statusCode == "000") {
                
                $arr = $result->data;
                usort($arr, function($a, $b) {
                    return $a->jarak <=> $b->jarak;
                });

                $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>$arr]);
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>$result->message]);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }    

    public function detailMasjid(Request $req)
    {
        try{
            date_default_timezone_set('Asia/Jakarta');
            $key = Str::of(Cache::get('key','merchant:'.date('Y-m-d').$req->id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $send = [
                'headers'   => $headers
            ];
            $client = new Client();
            $result = Cache::remember('merchant:'.date('Y-m-d').$req->id,env('CACHE_DURATION'),function() use ($client, $send, $req){
                return $client->get(env('API_MERCHANT').'masjid_detail/'.$req->id, $send)->getBody()->getContents();
            });
            $list = json_decode($result);
            // return $list->data;
            if ($result) {
                $arrJum = null;
                $jumat  = false;
                if ($list->data->sholat_jumat != null) {
                    $date   = date("h:i:s", strtotime($list->data->sholat_jumat->tanggal));
                    $day    = date("l", strtotime($list->data->sholat_jumat->tanggal));
                    // return date('l');
                    if (date("l") == "Friday") {
                        $fcm = new FCMController;
                        if (date("h:i:s") == "06:00:00") {
                            $fcm->sendMessageJumatan($date);
                        }
                        if (date("h:i:s") == "09:00:00") {
                            $fcm->sendMessageJumatan($date);
                        }
                        if (date("h:i:s") == "11:00:00") {
                            $fcm->sendMessageJumatan($date);
                        }
                    }
                    if ($day == date('l')) {
                        $jumat  = true;
                    }
                    $arrJum = [
                        "nama_khotib" => $list->data->sholat_jumat->nama_khotib,
                        "url_foto" => $list->data->sholat_jumat->url_foto,
                        "tema_khutbah_jumat" => $list->data->sholat_jumat->tema_khutbah_jumat,
                        "tanggal" => $date
                    ];
                }
                $arr = [
                    "uuid_masjid"       => $list->data->uuid_masjid,
                    "nama_masjid"       => $list->data->nama_masjid,
                    "alamat_masjid"     => $list->data->alamat_masjid,
                    "url_foto_masjid"   => $list->data->url_foto_masjid,
                    "latitude"          => $list->data->latitude,
                    "longitude"         => $list->data->longitude,
                    "foto_masjid"       => $list->data->foto_masjid, 
                    "data_urgensi"      => $list->data->data_urgensi, 
                    "data_harian"       => $list->data->data_harian, 
                    "data_unggulan"     => $list->data->data_unggulan,
                    "jumat"             => $jumat,
                    "sholat_jumat"      => $arrJum
                ];
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);                
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }   

    public function getNameMasjid($id)
    {
        try{
            $key = Str::of(Cache::get('key','merchant:'.date('Y-m-d').$id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $send = [
                'headers'   => $headers
            ];
            $client = new Client();
            $result = Cache::remember('merchant:'.date('Y-m-d').$id,env('CACHE_DURATION'),function() use ($client, $send, $id){
                return $client->get(env('API_MERCHANT').'masjid_detail/'.$id, $send)->getBody()->getContents();
            });
            $list = json_decode($result);
            if ($result) {
                $response = $list->data->nama_masjid;                
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function homeMasjid(Request $req)
    {
        try{
            $tokenData = MerchantToken::first();
            if (date('Y-m-d', strtotime($tokenData->created_at)) < date('Y-m-d')) {
                $this->loginForAuth();
            }
            $key = Str::of(Cache::get('key','merchantHome:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $body = json_encode([
                'radius' => '3',
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
            ]);
            $send = [
                'headers'   => $headers,
                'body'      => $body,
            ];
            $client = new Client();
            // return $client->post(env('API_MERCHANT').'tes_masjid_radius', $send)->getBody()->getContents();
            $result = Cache::remember('merchantHome:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->post(env('API_MERCHANT').'tes_masjid_radius', $send)->getBody()->getContents());
            });

            if ($result->statusCode == "000") {
                
                $arr = $result->data;
                usort($arr, function($a, $b) {
                    return $a->jarak <=> $b->jarak;
                });
                if (count($arr) > 3) {
                    $data_array = [];
                    $data_array_urgensi = [];
                    for ($i=0; $i < 3; $i++) { 
                        array_push($data_array, [
                            "uuid_masjid"       => $arr[$i]->uuid_masjid,
                            "nama_masjid"       => $arr[$i]->nama_masjid,
                            "alamat_masjid"     => $arr[$i]->alamat_masjid,
                            "url_foto_masjid"   => $arr[$i]->url_foto_masjid,
                            "latitude"          => $arr[$i]->latitude,
                            "longitude"         => $arr[$i]->longitude,
                            "jarak"             => $arr[$i]->jarak
                        ]);
                        if ($arr[$i]->program_urgunsi != null) {
                            for ($a=0; $a < 1; $a++) { 
                                array_push($data_array_urgensi, [
                                    "uuid_masjid" => $arr[$i]->uuid_masjid,
                                    "nama_masjid" => $arr[$i]->nama_masjid,
                                    "alamat_masjid" => $arr[$i]->alamat_masjid,
                                    "url_foto_masjid" => $arr[$i]->url_foto_masjid,
                                    "latitude" => $arr[$i]->latitude,
                                    "longitude" => $arr[$i]->longitude,
                                    "uuid_program_urgensi" => $arr[$i]->program_urgunsi[$a]->uuid_program_urgensi,
                                    "nama_program" => $arr[$i]->program_urgunsi[$a]->nama_program,
                                    "deskripsi_program" => $arr[$i]->program_urgunsi[$a]->deskripsi_program,
                                    "url_foto" => $arr[$i]->program_urgunsi[$a]->url_foto
                                ]);
                            }
                        }
                    }
                    $arr_response = [
                        'masjid' => $data_array,
                        'programUrgunsi' => $data_array_urgensi,
                    ];
                    $response = $arr_response;
                }else {
                    
                    $data_array = [];
                    $data_array_urgensi = [];
                    for ($i=0; $i < count($arr); $i++) { 
                        array_push($data_array, [
                            "uuid_masjid"       => $arr[$i]->uuid_masjid,
                            "nama_masjid"       => $arr[$i]->nama_masjid,
                            "alamat_masjid"     => $arr[$i]->alamat_masjid,
                            "url_foto_masjid"   => $arr[$i]->url_foto_masjid,
                            "latitude"          => $arr[$i]->latitude,
                            "longitude"         => $arr[$i]->longitude,
                            "jarak"             => $arr[$i]->jarak
                        ]);
                        if ($arr[$i]->program_urgunsi != null) {
                            // $data_array_urgensi = $arr[$i]->program_urgunsi;
                            for ($a=0; $a < 1; $a++) { 
                                array_push($data_array_urgensi, [
                                    "uuid_masjid" => $arr[$i]->uuid_masjid,
                                    "nama_masjid" => $arr[$i]->nama_masjid,
                                    "alamat_masjid" => $arr[$i]->alamat_masjid,
                                    "url_foto_masjid" => $arr[$i]->url_foto_masjid,
                                    "latitude" => $arr[$i]->latitude,
                                    "longitude" => $arr[$i]->longitude,
                                    "uuid_program_urgensi" => $arr[$i]->program_urgunsi[$a]->uuid_program_urgensi,
                                    "nama_program" => $arr[$i]->program_urgunsi[$a]->nama_program,
                                    "deskripsi_program" => $arr[$i]->program_urgunsi[$a]->deskripsi_program,
                                    "url_foto" => $arr[$i]->program_urgunsi[$a]->url_foto
                                ]);
                            }
                        }
                    }
                    $arr_response = [
                        'masjid' => $data_array,
                        'programUrgunsi' => $data_array_urgensi,
                    ];
                    $response = $arr_response;
                }
                
            }else {
                $response = [];
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    } 

    public function createdVa(Request $req, $id)
    {
        try{
            $key = Str::of(Cache::get('key','merchantVA:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $body = json_encode([
                "partnerId"         => $id,
                "expected_amount"   => (string)$req->amount,
                "bank"              => $req->purwantaraBankCode,
                "description"       => $req->note,
                "type"              => $req->transactionType,
                "uuid_masjid"       => $req->idMasjid,
                "id_user_hijrah"    => $req->idUserMobile
            ]);
            $send = [
                'http_errors' => false,
                'headers'   => $headers,
                'body'      => $body,
            ];
            $client = new Client();
            // return $client->post(env('API_MERCHANT').'ppn/createVA', $send)->getBody()->getContents();
            $result = Cache::remember('merchantVA:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                return json_decode($client->post(env('API_MERCHANT').'ppn/createVA', $send)->getBody()->getContents());
            });
            if ($result->statusCode == "000") {
                $merchant = MerchantTransaction::where('transactionId', $result->data->trxId)->first();

                $merchant->recipientBank    = $result->akunMasjid->bankCode;
                $merchant->recipientAccount = $result->akunMasjid->noRek;
                $merchant->recipientName    = $result->akunMasjid->atasNama;
                $merchant->VANumber         = $result->data->no_va;
                
                if ($merchant->save()) {
                    $arr = [
                        "_id"                   => $merchant->_id,
                        "idUserMobile"          => $merchant->idUserMobile,
                        "idMasjid"              => $merchant->idMasjid,
                        "transactionId"         => $merchant->transactionId,
                        "amount"                => $merchant->amount,
                        "note"                  => $merchant->note,
                        "purwantaraBankCode"    => $merchant->purwantaraBankCode,
                        "purwantaraBankImage"   => $this->bankPurwantaraImage($merchant->purwantaraBankCode),
                        "transactionType"       => $merchant->transactionType,
                        "statusTransfer"        => $merchant->statusTransfer,
                        "adminFee"              => $merchant->adminFee,
                        "recipientBank"         => $merchant->recipientBank,
                        "recipientAccount"      => $merchant->recipientAccount,
                        "recipientName"         => $merchant->recipientName,
                        "VANumber"              => $merchant->VANumber
                    ];
                    $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>$arr]);
                }else {
                    $response = json_encode(['statusCode'=>'333', 'message'=>'Gagal simpan data VA']);
                }
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>$result->message]);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }     

    public function listBankPurwantara()
    {
        try{
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $send = [
                'http_errors' => false,
                'headers'   => $headers,
            ];

            $client = new Client();
            $result = Cache::remember('merchantListBank:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->get(env('API_MERCHANT').'ppn/listChannel',$send)->getBody()->getContents());
            });
            if ($result->statusCode == "000") {
                $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>$result->data->virtual_account]);
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>$result->message]);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    } 

    public function bankPurwantaraImage($bankName)
    {
        try{
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $send = [
                'http_errors' => false,
                'headers'   => $headers,
            ];

            $client = new Client();
            $result = Cache::remember('merchantListBank:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send, $bankName){
                $list = json_decode($client->get(env('API_MERCHANT').'ppn/listChannel',$send)->getBody()->getContents());
                $image = '';
                foreach($list->data->virtual_account as $val){
                    if ($val->name == $bankName) {
                        $image = $val->image;
                    }
                }
                return $image;
            });
            $response = $result;
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }   

    public function akunBankMasjid($id)
    {
        try{
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $body = json_encode([
                "uuid_masjid" => $id
            ]);
            $send = [
                'http_errors' => false,
                'headers'   => $headers,
                'body' => $body
            ];

            $client = new Client();
            $result = Cache::remember('merchantListBank:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                
                return json_decode($client->post(env('API_MERCHANT').'masjid/akun',$send)->getBody()->getContents());
            });
            // return json_encode($result->data);
            if ($result->statusCode == "000") {
                $response = json_encode($result->data);
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>$result->message]);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    } 

    public function callbackMerchant(Request $req)

    {
        try{
            $data = MerchantTransaction::where('transactionId', $req->data['trxId'])->first();
            if ($req->statusCode == "000") {
                $data->statusTransfer = "Success";
            }else {
                $data->statusTransfer = "Filed";
            }

            if ($data->save()) {
                $response = json_encode(['statusCode'=>'000', 'message'=>'Data berhasil di simpan']);
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>'Data gagal di simpan']);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
    public function sendCallbackMerchant($id)
    {
        
        try{
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $merchant = MerchantTransaction::where('_id', $id)->first();
            if ($merchant) {
                $body = json_encode([
                    'statusCode'=>'000', 
                    'message'=>'Sukses',
                    'data'=> $merchant
                ]);
                $send = [
                    'http_errors' => false,
                    'headers'   => $headers,
                    'body' => $body
                ];
    
                $client = new Client();
                $result = Cache::remember('merchantListBank:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client, $send){
                    
                    return $client->post(env('API_MERCHANT').'financial/callbackDisbursement',$send)->getBody()->getContents();
                });
    
                if ($result) {
                    $response = json_encode(['statusCode'=>'000', 'message'=>'Sukses']);
                }else {
                    $response = json_encode(['statusCode'=>'333', 'message'=>'Gagal kirim data ke merchant']);
                }
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function insertKeuanganMasjid($arr)
    {
        try{
            $token  = json_decode($this->refreshAuth());
            $data   = $token->data;
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'Authorization' => $data->token_type.' '. $data->access_token,
            ];
            $body = json_encode([
                'statusCode'=>'000', 
                'message'=>'Sukses',
                'data'=> $arr
            ]);
            $send = [
                'http_errors' => false,
                'headers'   => $headers,
                'body' => $body
            ];

            $client = new Client();
            $result =  $client->post(env('API_MERCHANT').'financial/callbackDonasi',$send)->getBody()->getContents();

            if ($result) {
                $response = json_encode(['statusCode'=>'000', 'message'=>'Sukses']);
            }else {
                $response = json_encode(['statusCode'=>'333', 'message'=>'Gagal kirim data ke merchant']);
            }
            
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
}