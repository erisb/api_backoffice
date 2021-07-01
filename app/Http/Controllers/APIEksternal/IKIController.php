<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;


class IKIController extends Controller
{

    public function __construct()
    {
        $this->middleware('onlyJson', ['only' => ['processPPOB', 'continuePaymentPPOB']]);
    }

    
    // Start Package Produk //
    public function getAllCategory()
    {
        try {
            $client = new Client();

            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').'{}'.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));
            
            $resultIKI = $client->get(env('IKI_URL_PRODUK').'category-produk/get',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ]
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getAllPackageProduk()
    {
        try {
            $client = new Client();

            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').'{}'.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));

            $resultIKI = $client->get(env('IKI_URL_PRODUK').'package-produk/get',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ]
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getAllPackageProdukbyCategory(Request $req)
    {
        try {
            $client = new Client();

            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').'{}'.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));

            $resultIKI = $client->get(env('IKI_URL_PRODUK').'package-produk/get',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ],
                'query' => [
                    "category_id" => $req->id_kategori
                ]
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getAllPackageProdukbyEdr(Request $req)
    {
        try {
            $client = new Client();

            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').'{}'.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));

            $resultIKI = $client->get(env('IKI_URL_PRODUK').'package-produk/get',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ],
                'query' => [
                    "produk_id_edr" => $req->id_edr,
                    "trxId" => $req->id_trx
                ]
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    // End Package Produk //

    // Start PPOB Transaction //

    public function processPPOB(Request $req)
    {
        try {
            $client = new Client();

            $bodyParams = json_encode([
                "trxId" => $req->id_trx,
                "groupProduct" => $req->grup_produk,
                "productId" => $req->id_produk,
                "data" => $req->data
            ]);
            
            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').''.$bodyParams.''.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));
            
            $resultIKI = $client->post(env('IKI_URL_PPOB').'process',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ],
                'body' => $bodyParams
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function continuePaymentPPOB(Request $req)
    {
        try {
            $client = new Client();

            $bodyParams = json_encode([
                "trxId" => $req->id_trx
            ]);
            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $payload = env('IKI_USERNAME').''.$bodyParams.''.$datetime;
            $signature = hash_hmac('sha256', $payload, ENV('IKI_SECRET'));

            $resultIKI = $client->post(env('IKI_URL_PPOB').'continue_payment',[
                'headers' => [
                    "username" => env('IKI_USERNAME'),
                    "payloadcode" => $signature,
                    "timestamp" => $datetime
                ],
                'body' => $bodyParams
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    // End PPOB Transaction //

    public function getPayloadCode()
    {
        try {
            $client = new Client();

            $datetime = Carbon::now('Asia/Jakarta')->getPreciseTimestamp(3);
            $paramBody = json_encode([
                "username" => env('IKI_USERNAME'),
                "timestamp" => $datetime,
                "payload" => '{}'
            ]);

            $resultIKI = $client->post(env('IKI_URL_PRODUK').'generate_token',[
                'body' => $paramBody
            ])->getBody()->getContents();
            
            $response = $resultIKI;

        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

}
