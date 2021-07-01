<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\TransferTransactions;
use App\BankCodes;
use App\UserMobiles;
use App\CashTransactions;
use App\MerchantTransaction;
use App\Http\Controllers\LogTransactionController;
use App\Http\Controllers\Mail\HijrahEmailController;
use App\Http\Controllers\APIEksternal\HijrahMerchantController;
class OyController extends Controller
{

    public function accountInquiry(Request $req)
    {
        
        try {
            
            $client = new Client();

            $body = json_encode([
                'bank_code'  => $req->bankCode,
                'account_number'  => $req->accountNumber,
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/account-inquiry", $send)->getBody()->getContents();
            $data = json_decode($result);

            $bankCode = BankCodes::where('bankCode', (string) $data->bank_code)->first();

            $arr_status = [
                "code"      => $data->status->code,
                "message"   => $data->status->message
            ];
            $arr = [
                "status" => $arr_status,
                "bank_code" => $data->bank_code,
                "bank_name" => $bankCode->bankName,
                "bank_image" => $bankCode->bankImage,
                "account_number" => $data->account_number,
                "account_name" => $data->account_name,
                "timestamp" => $data->timestamp,
                "id" => $data->id,
                "invoice_id" => $data->invoice_id
            ];
            if ($data->status->code == "000") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
    
    public function disbursement($id)
    {
        try {
            
            $tf = TransferTransactions::where('_id', $id)->first();
            $user = UserMobiles::where('_id', $tf->idUserMobile)->first();

            $client = new Client();

            $body = json_encode([
                'recipient_bank'    => $tf->recipientBank,
                'recipient_account' => $tf->recipientAccount,
                'amount'            => $tf->nominal,
                'note'              => $tf->note,
                'partner_trx_id'    => $tf->transferId,
                'email'             => $user->email
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/remit", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "101" || $data->status->code == "000") {

                $tf->transferId       = $data->partner_trx_id;
                $tf->trxId            = $data->trx_id;
                $tf->recipientBank    = $data->recipient_bank;
                $tf->nominal          = $data->amount;
                $tf->statusTransfer   = $data->status->message;

                if ($tf->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $tf]);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error save data transfer']);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Error Transfer', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
    
    public function disbursementStatus(Request $req)
    {
        try {
            
            $client = new Client();

            $body = json_encode([
                'partner_trx_id'    => $req->partnerTrxId,
                'send_callback'     => 'true',
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/remit-status", $send)->getBody()->getContents();
            $data = json_decode($result);
            
            if ($data->status->code == "000") {
                $insert = Transfertransactions::where('transferId', $data->partner_trx_id)->first();

                if ($insert->statusTransfer == "Request is Processed") {
                    $insert->recipientName  = $data->recipient_name;
                    $insert->statusTransfer = $data->status->message;

                    if ($insert->save()) {
                        $log = new LogTransactionController;
                        $status = "";
                        if ($insert->statusTransfer == "Success") {
                            $status = 0;
                        }else{
                            $status = 1;
                        }
                        $desc = "Transfer ke ".$insert->recipientName;
                        $log->insertOyTransfer($insert->_id, $insert->idUserMobile, $insert->transferId, $insert->amount,$status, $desc);    
                        $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $insert]);
                    }else {
                        $response = json_encode(['statusCode' => '460', 'message' => 'Error Update Status']);
                    }
                }else {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $insert]);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
    
    public function disbursementBalance()
    {
        try {
            
            $client = new Client();
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers
            ];

            $result = $client->get(env('API_OY')."api/balance", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "000") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
    
    public function callbackDisbursement(Request $req)
    {
        try {
            $sub = substr($req->partner_trx_id,0,6);
            if ($sub == "SPSTRX") {
                $data = CashTransactions::where('transactionId', $req->partner_trx_id)->first();

                $data->statusTransfer = $req->status['message'];

                if ($data->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Save data OY sukses']);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error Update Status']);
                }
            }else if($sub == "SPSMRC") {
                $data = MerchantTransaction::where('transactionId', $req->partner_trx_id)->first();

                $data->recipientName    = $req->recipient_name;
                $data->statusTransfer   = $req->status['message'];

                if ($data->save()) {
                    $merchant = new HijrahMerchantController;
                    $merchant->sendCallbackMerchant($data->_id);
                    $log = new LogTransactionController;
                    $status = "";
                    if ($data->statusTransfer == "Success") {
                        $status = 0;
                    }else{
                        $status = 1;
                    }
                    $desc = $data->transactionType." sebesar Rp. ".number_format($data->amount, 2);
                    $log->insertMerchant($data->_id, $data->idUserMobile, $data->transferId, $data->amount,$status, $desc);
                    $response = json_encode(['statusCode' => '000', 'message' => 'Save data OY sukses']);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error Update Status']);
                }
            }else {
                $data = Transfertransactions::where('transferId', $req->partner_trx_id)->first();

                $data->recipientName = $req->recipient_name;
                $data->statusTransfer = $req->status['message'];

                if ($data->save()) {
                    $log = new LogTransactionController;
                    $status = "";
                    if ($data->statusTransfer == "Success") {
                        $status = 0;
                    }else{
                        $status = 1;
                    }
                    $desc = "Transfer ke ".$data->recipientName;
                    $log->insertOyTransfer($data->_id, $data->idUserMobile, $data->transferId, $data->amount,$status, $desc);
                    $response = json_encode(['statusCode' => '000', 'message' => 'Save data OY sukses']);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error Update Status']);
                }
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function partnerTrxId()
    {
        
        try {
            date_default_timezone_set("Asia/Jakarta");

            $start = 1;
            $dates = date('dmy');
            
            $result = TransferTransactions::select('transferId', 'created_at')->latest()->first();
            if ($result) { 
                if (date('dmy', strtotime($result->created_at)) == $dates) {
                    $cd = Str::substr($result, -4);
                    $start = $start+(int)$cd;
                }
            }
            $num = sprintf("%04d", $start);
            $refCd = "SPSTRF".$dates.$num;
            
            $response = $refCd;
                
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function inOutMoney($id)
    {
        try {
            
            $dataCash = CashTransactions::where('_id', $id)->first();
            $client = new Client();
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'x-oy-username' => env('USERNAME_OY'),
                'x-api-key'     => env('API_KEY_OY')
                ];
            $body = [
                'partner_trx_id'            => $dataCash->transactionId, 
                'receiver_phone_number'     => $dataCash->recvPhoneNumber,
                'amount'                    => $dataCash->amount,
                'transaction_type'          => $dataCash->transactionType,
                'offline_channel'           => $dataCash->channel
                ];
            $bodys = json_encode($body);
            $send = [
                'headers'   => $headers,
                'body'      => $bodys
            ];

            $result = $client->post(env('API_OY')."api/offline-create", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "000") {
                $dataCash->amount           =  $data->amount;
                $dataCash->code             =  $data->code;
                $dataCash->trxId            =  $data->trx_id;
                $dataCash->transactionType  =  $data->transaction_type;
                $dataCash->status           =  $data->status->message;
                $dataCash->inactive_at      =  $data->inactive_at;
                $dataCash->expired_at       =  $data->expired_at;
                if ($dataCash->save()) {
                    // $mail = new HijrahEmailController;
                    // $mail->sendEmailSetorTarik($dataCash->_id);
                    if ($dataCash->transactionType == "CASH_IN") {
                        $this->disbursementSetorTarik($dataCash->_id);
                    }
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $dataCash]);
                }else {
                    $response = json_encode(['statusCode' => '333', 'message' => 'Gagal update data Setor']);
                }
            }elseif ($data->status->code == "102") {
                
                $dataCash->amount           =  $data->amount;
                $dataCash->code             =  $data->code;
                $dataCash->trxId            =  $data->trx_id;
                $dataCash->transactionType  =  $data->transaction_type;
                $dataCash->status           =  $data->status->message;
                $dataCash->inactive_at      =  $data->inactive_at;
                $dataCash->expired_at       =  $data->expired_at;
                if ($dataCash->save()) {
                    $mail = new HijrahEmailController;
                    $mail->sendEmailSetorTarik($dataCash->_id);
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $dataCash]);
                }else {
                    $response = json_encode(['statusCode' => '333', 'message' => 'Gagal update data Setor']);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;

    }

    public function transactionInfo($id)
    {
        try {
            $dataCash = CashTransactions::where('_id', $id)->first();

            $client = new Client();
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $body = [
                'partner_trx_id'    => $dataCash->transactionId,
                'send_callback'     => 'true'
                ];
            $bodys = json_encode($body);
            $send = [
                'headers'   => $headers,
                'body'      => $bodys
            ];

            $result = $client->post(env('API_OY')."api/offline-info", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "000") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function refreshCode($id)
    {
        try {
            $dataCash = CashTransactions::where('_id', $id)->first();
            
            $client = new Client();
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $body = [
                'partner_trx_id'    => $dataCash->transactionId,
                ];
            $bodys = json_encode($body);
            $send = [
                'headers'   => $headers,
                'body'      => $bodys
            ];

            return $result = $client->post(env('API_OY')."api/offline-refresh-code", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "000") {
                
                $dataCash->code             =  $data->code;
                $dataCash->inactive_at      =  $data->inactive_at;
                $dataCash->expired_at       =  $data->expired_at;

                if ($dataCash->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $dataCash]);
                }else {
                    $response = json_encode(['statusCode' => '333', 'message' => 'Gagal update data Setor']);
                }
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Sukses', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function callbackSetorTarik(Request $req)
    {
        try {            
            $data = CashTransactions::where('transactionId', $req->partner_trx_id)->first();
            if ($data->transactionType == "CASH_IN") {
                $this->disbursementSetorTarik($data->_id);
            }
            $data->status  =  $req->status['message'];

            if ($data->save()) {
                $log = new LogTransactionController;
                $status = "";
                if ($data->status == "Success") {
                    $status = 0;
                }else{
                    $status = 1;
                }
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertSetorTarik($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);
                
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                $response = json_encode(['statusCode' => '333', 'message' => 'Gagal update data Setor']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function disbursementSetorTarik($id)
    {
        try {
            
            $tf = CashTransactions::where('_id', $id)->first();
            $user = UserMobiles::where('_id', $tf->idUserMobile)->first();

            $client = new Client();

            $body = json_encode([
                'recipient_bank'    => $tf->recipientBank,
                'recipient_account' => $tf->recipientAccount,
                'amount'            => $tf->totalTransaksi,
                'note'              => "",
                'partner_trx_id'    => $tf->transactionId,
                'email'             => $user->email
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/remit", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "101" || $data->status->code == "000") {

                $tf->trxIdTransfer    = $data->trx_id;
                $tf->recipientBank    = $data->recipient_bank;
                $tf->statusTransfer   = $data->status->message;

                if ($tf->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $tf]);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error save data transfer']);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Error Transfer', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    

    public function disbursementMerchent(Request $req)
    {
        try {
            
            $tf = MerchantTransaction::where('transactionId', $req->data['trxId'])->first();
            $user = UserMobiles::where('_id', $tf->idUserMobile)->first();

            $client = new Client();

            $body = json_encode([
                'recipient_bank'    => $tf->recipientBank,
                'recipient_account' => $tf->recipientAccount,
                'amount'            => $tf->amount,
                'note'              => $tf->note,
                'partner_trx_id'    => $tf->transactionId,
                'email'             => $user->email
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/remit", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "101" || $data->status->code == "000") {

                $tf->trxIdMerchant    = $data->trx_id;
                $tf->recipientBank    = $data->recipient_bank;
                $tf->statusTransfer   = $data->status->message;

                if ($tf->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $tf]);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error save data transfer']);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Error Transfer', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function mootaDisbursementMerchant($id)
    {
        try {
            
            $tf = MerchantTransaction::where('_id', $id)->first();
            $user = UserMobiles::where('_id', $tf->idUserMobile)->first();

            $client = new Client();

            $body = json_encode([
                'recipient_bank'    => $tf->recipientBank,
                'recipient_account' => $tf->recipientAccount,
                'amount'            => $tf->amount,
                'note'              => $tf->note,
                'partner_trx_id'    => $tf->transactionId,
                'email'             => $user->email
            ]);
            $headers = [
                'Content-Type'  => 'application/json', 
                'Accept'        => 'application/json',
                'X-OY-Username' => env('USERNAME_OY'),
                'X-Api-Key'     => env('API_KEY_OY')
                ];
            $send = [
                'headers'   => $headers,
                'body'      => $body
            ];

            $result = $client->post(env('API_OY')."api/remit", $send)->getBody()->getContents();
            $data = json_decode($result);

            if ($data->status->code == "101" || $data->status->code == "000") {

                $tf->trxIdMerchant    = $data->trx_id;
                $tf->recipientBank    = $data->recipient_bank;
                $tf->statusTransfer   = $data->status->message;

                if ($tf->save()) {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $tf]);
                }else {
                    $response = json_encode(['statusCode' => '460', 'message' => 'Error save data transfer']);
                }
            }else {
                $response = json_encode(['statusCode' => '460', 'message' => 'Error Transfer', 'data' => $data->status->message]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

}
