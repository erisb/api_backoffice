<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;

class RestoranController extends Controller
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

    public function lokasi(Request $req)
    {
        try{
            $keyGoogle = env('KEY_GOOGLE');
            $type = env('TYPE_RESTAURANT_GOOGLE');
            $radius = env('RADIUS_GOOGLE');
            $location = $req->lokasi;

            $key = Str::of(Cache::get('key','apiRestoran:'.date('Y-m-d').':'.$location))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiRestoran:'.date('Y-m-d').':'.$location,env('CACHE_DURATION'),function() use ($client,$keyGoogle,$type,$radius,$location){
                return json_decode($client->get(env('API_GOOGLE'),
                        ['query' => ['key' => $keyGoogle,'type' => $type, 'radius' => $radius, 'location' => $location]])->getBody()->getContents());
            });
            
            $response = response()->json(['statusCode'=>'000','message'=>'Berhasil','data'=>$result->results]);
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
}