<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\TransactionTopup;
use App\UserMobiles;
use App\PaymentCodes;
use Illuminate\Support\Str;
use App\Http\Controllers\APIEksternal\MobilePulsaController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Storage;
use Illuminate\Support\Facades\Cache;
use App\Events\CacheFlushEvent;
use App\Helpers\FormatDate;
use Carbon\Carbon;
use DateTime;

class PurwantaraController extends Controller
{
    public function __construct()
    {
        $this->middleware('onlyJson', ['only' => ['callbackPurwantara']]);
    }
    public function callbackPurwantara(Request $req){
        try {
            $data = PaymentCodes::where('trxId', $req->external_id)->first();

            $data->status = $req->status;
            $client = new Client;

            if($data -> save()) {
                if($req->status == "PAID"){
                    $arr = [
                        'uuid_masjid' => $data->idMasjid,
                        'kategori_keuangan' => 'pemasukan',
                        'jenis_keuangan' => $data->paymentFor,
                        'title' => $data->deskripsi,
                        'tanggal_transaksi' => substr($data->updated_at,0, 10),
                        'keterangan' => $data->deskripsi,
                        'jumlah' => $data->nominal,
                        'id_user_hijrah' => $data->idUserMobile,
                        'url_foto' => ''
                    ];

                    $merchant = new HijrahMerchantController;
                    $merchant->insertKeuanganMasjid($arr);
                }
                $response = json_encode(['statusCode' => '000', 'message' => 'Save data Purwantara Sukses']);                
            }else{
                $response = json_encode(['statusCode' => '000','message' => 'Error Update Status']);
            }
        } catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function listChannel(Request $req)
    {
        try{
            $client = new Client();
            
            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_PPN'),
                ];

            $send = [
                'headers'   => $headers
            ];
            
            $result    = json_decode($client->get(env('URL_PPN').'channel', $send)->getBody()->getContents());
            if ($req->donasi == true) {
                $response  = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data->virtual_account]);    
            }else{
                $response  = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
            }
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function purwantaraVA(Request $req, $id)
    {
        date_default_timezone_set("Asia/Jakarta");

        try{
            $client = new Client();
            $addDay = strtotime('+1 day', strtotime(date("Y-m-d H:i:s")));
            $exp = date(DATE_ISO8601, $addDay);
            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_PPN'),
                'Content-Type'  => 'application/x-www-form-urlencoded'
                ];

            $body = [
                'expected_amount' => $req->amount,
                'name' => 'PT Sarana Pembayaran Syariah',
                'bank' => $req->channel,
                'description' => $req->note,
                'expired_at' => $exp,
                'external_id' => $id,
                'merchant_trx_id' => $id
            ];

            $send = [
                'http_errors'=>false,
                'headers'   => $headers,
                'form_params'  => $body
            ];

            $result    = json_decode($client->post(env('URL_PPN').'virtual-account', $send)->getBody()->getContents());

            $data = new PaymentCodes;
            $data->idMasjid = $req->idMasjid;
            $data->paymentType = "va";
            $data->paymentCode = $result->data->va_number;
            $data->paymentFor = $req->transactionType;
            $data->nominal = $req->amount;
            $data->channel = $req->channel;
            $data->deskripsi = $req->note;
            $data->expired = $exp;
            $data->trxId = $id;
            $data->status = "Waiting for Payment";
            $data->idUserMobile = $req->idUserMobile;

            if($data->save()){
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else{
                $response = json_encode(['statusCode' => '800', 'message' => 'Error save Number VA']);
            }
            
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function purwantaraQrisShoope(Request $req)
    {
        try {
            $trans  = TransactionTopup::where('_id', $req->id)->first();
            $user   = UserMobiles::where('_id', $trans->idUserMobile)->first();
            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_PPN'),
                'Content-Type'  => 'application/x-www-form-urlencoded'
                ];

            $body = [
                "amount"                        => $trans->totalTransfer,
                "transaction_description"       => $trans->mpType." ".$trans->nominalTopup,
                "customer_email"                => $user->emailUser,
                "customer_first_name"           => $user->namaUser,
                "customer_last_name"            => $trans->operatorTopup." ".$trans->nominalTopup,
                "customer_phone"                => $user->noTelpUser,
                "payment_options_referral_code" => "",
                "payment_channel"               => $trans->paymentType,
                "channel_id"                    => env('CHANNEL_ID'),
                "callback_url"                  => ENV('CALLBACK_PURWANTARA'),
                "additional_data"               => "",
                "order_id"                      => $trans->refId,
                "payment_method"                => "wallet",
                "merchant_trx_id"               => $trans->refId
            ];

            $send = [
                'http_errors'=>false,
                'headers'   => $headers,
                'form_params'  => $body
            ];

            $client = new Client();
            $result    = json_decode($client->post(env('URL_PPN').'qris', $send)->getBody()->getContents());

            if ($result->status == 201) {
                
                $trans->uuidPurwantara  = $result->data->uuid;
                $trans->expiredTime     = $result->data->expired_time;
                $trans->qrString        = $result->data->qr_string;
                $trans->qrUrl           = $result->data->qr_url;

                if ($trans->save()) {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $trans]);
                }else {
                    return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Penerima Transfer']);
                }
            }

        } catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;  
    }    

    public function purwantaraEwalletOvo(Request $req)
    {
        try {
            $trans  = TransactionTopup::where('_id', $req->id)->first();
            $user   = UserMobiles::where('_id', $trans->idUserMobile)->first();
            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_PPN'),
                'Content-Type'  => 'application/x-www-form-urlencoded'
                ];

            $body = [
                "customer_phone"    =>  $req->phoneNumber,
                "customer_email"    =>  $user->emailUser,
                "payment_channel"   =>  $trans->paymentType,
                "external_id"       =>  $trans->refId,
                "description"       =>  "Topup ".$trans->mpType." ".$trans->nominalTopup,
                "amount"            =>  $trans->totalTransfer
            ];

            $send = [
                'http_errors'=>false,
                'headers'   => $headers,
                'form_params'  => $body
            ];

            $client = new Client();
            // return $client->post(env('URL_PPN').'ewallet/ovo', $send)->getBody()->getContents();
            $result    = json_decode($client->post(env('URL_PPN').'ewallet/ovo', $send)->getBody()->getContents());

            if ($result->status == 201) {
                if ($result->data->status == "ACTIVE") {
                    $trans->messageTopup       = "SUCCESS";
                }else {
                    $trans->messageTopup       = "FILED";
                }
                
                $trans->uuidPurwantara  = $result->data->uuid;
                $trans->ovoStatus       = $result->data->status;

                if ($trans->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $trans]);
                }else {
                    $response = json_encode(['statusCode' => '461', 'message' => 'Error Save Data Penerima Transfer']);
                }
            }else {
                # code...
            }

        } catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;  
    }
}
