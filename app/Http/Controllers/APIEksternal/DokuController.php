<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;
use App\MerchantTransaction;
use App\UserMobiles;
use App\BankCodes;
use App\Http\Controllers\APIEksternal\HijrahMerchantController;
use App\Http\Controllers\APIEksternal\OyController;

class DokuController extends Controller
{

    public function __construct()
    {
        $this->middleware('onlyJson', ['only' => ['callbackDokuVA']]);
    }

    public function createdVA($id)
    {

        try {
            $data = MerchantTransaction::where('_id', $id)->first();
            $user = UserMobiles::where('_id', $data->idUserMobile)->first();
            $dateGMT = gmdate("Y-m-d\TH:i:s\Z");
            $reqId =  (string) Str::uuid();
            $reqTarget = "/bsm-virtual-account/v2/payment-code";
            $params = json_encode([
                "order" => [
                    "invoice_number" => $data->transactionId,
                    "amount" => $data->amount
                ],
                "virtual_account_info" => [
                    "expired_time" => 60,
                    "reusable_status" => false
                ],
                "customer" => [
                    "name" => $user->namaUser,
                    "email" => $user->emailUser
                ],
            ]);
            $diger =  base64_encode(hash('sha256', $params, true));
            $sign = "Client-Id:".env('CLIENT_ID_DOKU') ."\n". 
                    "Request-Id:".$reqId . "\n".
                    "Request-Timestamp:".$dateGMT ."\n". 
                    "Request-Target:".$reqTarget ."\n".
                    "Digest:".$diger;
            $signatur = "HMACSHA256=".base64_encode(hash_hmac('sha256', $sign, env('SECRET__KEY_DOKU'), true));
            $headers = [
                "Content-Type" => "application/json",
                "Client-Id" => env('CLIENT_ID_DOKU'),
                "Request-Timestamp" => $dateGMT,
                "Request-Id" => $reqId,
                "Signature" => $signatur
            ];

            $send = [
                'headers' => $headers,
                'body' => $params
            ];
            $client = new Client();
            $result = json_decode($client->post(env('API_DOKU') . $reqTarget,$send)->getBody()->getContents());
            
            $data->dokuUuid     = $reqId;
            $data->VANumber     = $result->virtual_account_info->virtual_account_number;
            $data->expiredDate  = $result->virtual_account_info->expired_date;

            if ($data->save()) {
                $code       = BankCodes::where('bankCode', $data->spsBankCode)->first();

                $arr =[
                    "_id"               => $data->_id,
                    "idUserMobile"      => $data->idUserMobile,
                    "idMasjid"          => $data->idMasjid,
                    "transactionId"     => $data->transactionId,
                    "recipientBank"     => $data->recipientBank,
                    "recipientAccount"  => $data->recipientAccount,
                    "recipientName"     => $data->recipientName,
                    "amount"            => $data->amount,
                    "note"              => $data->note,
                    "transactionType"   => $data->transactionType,
                    "statusTransfer"    => $data->statusTransfer,
                    "spsBankNumber"     => $data->spsBankNumber,
                    "spsBankCode"       => $data->spsBankCode,
                    "spsBankName"       => $code->bankName,
                    'spsBankImage'      => $code->bankImage,
                    "adminFee"          => $data->adminFee,
                    "totalTransfer"     => $data->totalTransfer,
                    "dokuUuid"          => $data->dokuUuid,
                    "VANumber"          => $data->VANumber,
                    "expiredDate"       => $data->expiredDate
                ];
                $response = json_encode(array('statusCode' => '000', 'message' => "Success", 'data' => $arr));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Nomor VA"));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function callbackDokuVA(Request $req)
    {
        try {
            $data = MerchantTransaction::where('transactionId', $req['order']['invoice_number'])->first();

            $data->statusVA = $req['transaction']['status'];

            if ($data->save()) {
                $oy = new OyController;
                // $response = $oy->mootaDisbursementMerchant($data->_id);
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses Menyimpan Data"));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Sukses Menyimpan Data"));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
