<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;

class MasjidController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['lokasi','cari']]);
    }

    public function lokasi(Request $req)
    {
        try{
            $keyGoogle = env('KEY_GOOGLE');
            $type = env('TYPE_MOSQUE_GOOGLE');
            $radius = env('RADIUS_GOOGLE');
            $location = $req->lokasi;

            $key = Str::of(Cache::get('key','apiMasjid:'.date('Y-m-d').':'.$location))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiMasjid:'.date('Y-m-d').':'.$location,env('CACHE_DURATION'),function() use ($client,$keyGoogle,$type,$radius,$location){
                return json_decode($client->get(env('API_GOOGLE'),
                        ['query' => ['key' => $keyGoogle,'type' => $type, 'radius' => $radius, 'location' => $location]])->getBody()->getContents());
            });
            
            $response = json_encode(['statusCode' => '000','message' => 'Berhasil','data' => $result->results]);
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function cari(Request $req){
        try{
            $keyGoogle = env('KEY_GOOGLE');
            $type = env('TYPE_MOSQUE_GOOGLE');
            $radius = env('RADIUS_GOOGLE');
            $location = $req->lokasi;
            $namaMasjid = $req->namaMasjid;
            $arrTemp1 = [];
            $arrTemp2 = [];
            $arrMasjid = [];

            $key = Str::of(Cache::get('key','apiMasjid:'.date('Y-m-d').':'.$location.':'.$namaMasjid))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiMasjid:'.date('Y-m-d').':'.$location.':'.$namaMasjid,env('CACHE_DURATION'),function() use ($client,$keyGoogle,$type,$radius,$location){
                return json_decode($client->get(env('API_GOOGLE'),
                        ['query' => ['key' => $keyGoogle,'type' => $type, 'radius' => $radius, 'location' => $location]])->getBody()->getContents());
            });
            
            foreach ($result->results as $val)
            {
                similar_text(ucwords($namaMasjid),$val->name,$perc);
                if ($perc == 100)
                {
                    array_push($arrTemp1,['name'=>$val->name,'vicinity'=>$val->vicinity]);
                } else {
                    $arrTemp1;
                }
            }
            foreach ($result->results as $val)
            {
                if (count($arrTemp1) > 0) {
                    $arrTemp2 = $arrTemp1;
                } else {
                    similar_text(ucwords($namaMasjid),$val->name,$perc);
                    if ($perc < 100 && $perc >= 50)
                    {
                        array_push($arrTemp2,['name'=>$val->name,'vicinity'=>$val->vicinity]);
                    } else {
                        $arrTemp2;
                    }
                }
            }
            foreach ($result->results as $val)
            {
                if (count($arrTemp2) > 0) {
                    $arrMasjid = $arrTemp2;
                } else {
                    similar_text(ucwords($namaMasjid),$val->name,$perc);
                    if ($perc < 50 && $perc >= 0)
                    {
                        array_push($arrMasjid,['name'=>$val->name,'vicinity'=>$val->vicinity]);
                    } else {
                        $arrMasjid;
                    }
                }
            }
            $response = json_encode(['statusCode' => '000','message' => 'Berhasil','data' => $arrMasjid]);
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