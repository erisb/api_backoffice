<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;

class KalenderHijriyahController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['hijriyah']]);
    }

    public function hijriyah(Request $req)
    {
        try{
            $params = $req->tanggal;
            
            $key = Str::of(Cache::get('key','apiKalender:'.$req->tanggal))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiKalender:' . $req->tanggal, env('CACHE_DURATION'), function () use ($client, $params) {
                return json_decode($client->get(
                    env('API_ALADHAN') . '/gToH',
                    ['form_params' => ['date' => $params]]
                )->getBody()->getContents());
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
}
