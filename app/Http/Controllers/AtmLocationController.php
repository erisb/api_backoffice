<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\AtmLocations;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class AtmLocationController extends Controller
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
        $this->middleware('onlyJson',['only'=>['search','lokasi','detail','getDataDoaBySearch','insert','update']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function search(Request $req){
        try {
            $key = Str::of(Cache::get('key', 'doa_search:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('doa_search:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                return Doa::where('prayerStatus','1')->where('prayerTitle','like',"%".$req->q."%")->get();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
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

    public function view(){
        try {
            $key = Str::of(Cache::get('key', 'doa:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('doa:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                return json_decode(Doa::where('prayerStatus','1')->orderBy('created_at', 'DESC')->get());
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
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

    public function detail(Request $req, $id){
        try {
            $key = Str::of(Cache::get('key', 'doa:' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $cek = Bookmark::where('imei', $req->imei)->count();
                if($cek > 0){
                    $isBookmark = 1;
                }else{
                    $isBookmark = 0;
                }
            $cache = Cache::remember('doa:' . $id, env('CACHE_DURATION'), function () use ($req, $id){
                return Doa::where('_id', $id)->first();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'isBookmark' => $isBookmark, 'data' => $cache]);
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
        try {
            $validatorUrlFoto = Validator::make($req->all(), AtmLocations::$rulesAtmImage, AtmLocations::$messages);
            $validatorFormat  = Validator::make($req->all(), AtmLocations::$rulesFormatAtmImage, AtmLocations::$messages);
            $validatorMax     = Validator::make($req->all(), AtmLocations::$rulesMaxAtmImage, AtmLocations::$messages);

            if ($validatorUrlFoto->fails()) {
                $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            } else {
                if ($req->hasFile('imgUrl')) {

                    $files = $req->file('imgUrl'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name
    
                    $filePath = '/icon_bank/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }
    
                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }
                $data = new AtmLocations;
                $data->namaLokasi   =  $req->namaLokasi;
                $data->latitude     =  $req->latitude;
                $data->longitude    =  $req->longitude;
                $data->imgUrl       =  env('OSS_DOMAIN') . $filePath;;
                $data->flag         =  $req->flag;
                if ($data->save()) {
                    Cache::forget('atm:' . date('Y-m-d'));
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data'=> $data));
                } else {
                    $response = json_encode(array('statusCode' => '633', 'message' => "Gagal Menyimpan Lokasi ATM"));
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'ATM','Add ATM - '.$req->namaLokasi,json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        try {
            $validatorUrlFoto = Validator::make($req->all(), AtmLocations::$rulesAtmImage, AtmLocations::$messages);
            $validatorFormat  = Validator::make($req->all(), AtmLocations::$rulesFormatAtmImage, AtmLocations::$messages);
            $validatorMax     = Validator::make($req->all(), AtmLocations::$rulesMaxAtmImage, AtmLocations::$messages);

            if ($validatorUrlFoto->fails()) {
                $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            } else {
                if ($req->hasFile('imgUrl')) {

                    $files = $req->file('imgUrl'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name
    
                    $filePath = '/icon_bank/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }
    
                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data = AtmLocations::where('_id', $id)->first();
                if ($data->imgUrl != "") {
                    $ex = explode("/", $data->imgUrl);
                    Storage::disk('oss')->delete('/icon_bank/' . $ex[4]);
                }
                if ($req->hasFile('imgUrl')) {

                    $files = $req->file('imgUrl'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name
    
                    $filePath = '/icon_bank/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }
    
                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }
                $data->namaLokasi   =  $req->namaLokasi;
                $data->latitude     =  $req->latitude;
                $data->longitude    =  $req->longitude;
                $data->imgUrl       =  env('OSS_DOMAIN') . $filePath;;
                $data->flag         =  $req->flag;
                if ($data->save()) {
                    Cache::forget('atm:' . date('Y-m-d'));
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data'=> $data));
                } else {
                    $response = json_encode(array('statusCode' => '634', 'message' => "Gagal Update Lokasi ATM"));
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'ATM','Update ATM - '.$req->namaLokasi,json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = AtmLocations::where('_id', $id)->first();
            $namaLokasi = $data->namaLokasi;

            if ($data->delete()) {
                Cache::forget('atm:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Lokasi Atm"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Atm','Delete Atm - '.$judulDoa,json_decode($response)->message));
        return $response;
    }

    public function lokasi(Request $req)
    {
        try{
            $keyGoogle = env('KEY_GOOGLE');
            $type = "textquery";
            $radius = env('RADIUS_GOOGLE');
            $location = "circle:".$radius."@".$req->lokasi;
            $input = "alfamart";

            $key = Str::of(Cache::get('key','searchAtm:'.date('Y-m-d').':'.$location))->explode(':')[1];
            event(new CacheFlushEvent($key));
            // $response = env('API_GMAPS')."?input=$input&inputtype=$type&locationbias=$location&key=$keyGoogle";
            $client = new Client();
            $result = json_decode($client->get("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=-6.26357257022097,106.78859215482424&radius=2000&keyword=alfamart&key=$keyGoogle")->getBody()->getContents());
            $count = count($result->results);
            $newArr = [];
            for ($i=0; $i < $count; $i++) { 
                array_push($newArr, [
                    'lokasi' => $result->results[$i]->geometry->location,
                    'nama' => $result->results[$i]->name,
                    'ketersediaan' => $result->results[$i]->opening_hours->open_now,
                    'rating' => $result->results[$i]->rating, 
                    'total_rating' => $result->results[$i]->user_ratings_total,
                    'alamat' => $result->results[$i]->vicinity
                ]);    
            }
            
            
            // $result = Cache::remember('searchAtm:'.date('Y-m-d').':'.$location,env('CACHE_DURATION'),function() use ($input,$client,$keyGoogle,$type,$radius,$location){
            //     return json_decode($client->get(env('API_GMAPS'),
            //             ['query' => ['input' => $input, 'inputtype' => $type, 'locationbias' => $location, 'key' => $keyGoogle]])->getBody()->getContents());
            // });
            
            $response = json_encode(['statusCode' => '000','message' => 'Berhasil','data' => $newArr]);
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
}
