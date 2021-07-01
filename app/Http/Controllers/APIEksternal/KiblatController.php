<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;

class KiblatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['arah']]);
    }

    public function arah(Request $req)
    {
        try{
            $latitude = $req->latitude; 
            $longitude = $req->longitude;
            
            $key = Str::of(Cache::get('key','apiKiblat:'.date('Y-m-d').':'.$latitude.':'.$longitude))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiKiblat:'.date('Y-m-d').':'.$latitude.':'.$longitude,env('CACHE_DURATION'),function() use ($client,$latitude,$longitude){
                return $client->get(env('API_ALADHAN').'/qibla/'.$latitude.'/'.$longitude)->getBody()->getContents();
            });
            
            $response = json_encode(['statusCode'=>'000', 'message'=>'Berhasil', 'data'=>json_decode($result)]);
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