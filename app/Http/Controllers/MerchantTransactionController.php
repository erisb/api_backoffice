<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\LogTransaction;
use App\MerchantTransaction;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;
use App\Events\DonasiEvent;
use App\Http\Controllers\APIEksternal\HijrahMerchantController;
use App\Http\Controllers\APIEksternal\DokuController;
use App\Http\Controllers\APIEksternal\PurwantaraController;

class MerchantTransactionController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLogin', ['only' => ['insertTransactionMerchant', 'detailDonasi', 'insertTransactionMerchantForVA']]);
        $this->middleware('onlyJson',['only'=>['insertTransactionMerchant', 'insertTransactionMerchantForVA']]);
    }

    public function insertTransactionMerchant(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'          => 'required',
            'idMasjid'              => 'required',
            'amount'                => 'required',
            'spsBankNumber'         => 'required',
            'spsBankCode'           => 'required',
            'transactionType'       => 'required',
        ]);

        try {
            $merchant = new HijrahMerchantController;
            $resultMerchant = json_decode($merchant->akunBankMasjid($req->idMasjid));
            $unik = $this->unikCode();
            $total = (int) $req->amount + $unik;
            $data = new MerchantTransaction;

            $data->idUserMobile         =  $req->idUserMobile;
            $data->idMasjid             =  $req->idMasjid;
            $data->transactionId        =  $this->transactionId();
            $data->recipientBank        =  $resultMerchant->bankCode;
            $data->recipientAccount     =  $resultMerchant->noRek;
            $data->recipientName        =  $resultMerchant->atasNama;
            $data->amount               =  $req->amount;
            $data->note                 =  $req->note;
            $data->transactionType      =  $req->transactionType;
            $data->statusTransfer       =  "BELUM BAYAR";
            $data->spsBankNumber        =  $req->spsBankNumber;
            $data->spsBankCode          =  $req->spsBankCode;
            $data->codeUnik             =  $unik;
            $data->adminFee             =  0;
            $data->totalTransfer        =  $total;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertMerchant($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);
                
                $ppn = new PurwantaraController;
                $response =  $ppn->purwantaraVA($req, $data->transactionId);
                // $response = json_encode(array('statusCode' => '000', 'message' => "Success", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan merchant transaction"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // Cache::forget('termcontents');
        // event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }

    public function insertTransactionDokuVA(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'          => 'required',
            'idMasjid'              => 'required',
            'amount'                => 'required',
            'spsBankNumber'         => 'required',
            'spsBankCode'           => 'required',
            'transactionType'       => 'required',
        ]);

        try {
            $merchant   = new HijrahMerchantController;
            $doku       = new DokuController;
            $resultMerchant = json_decode($merchant->akunBankMasjid($req->idMasjid));
            $data = new MerchantTransaction;

            $data->idUserMobile         =  $req->idUserMobile;
            $data->idMasjid             =  $req->idMasjid;
            $data->transactionId        =  $this->transactionId();
            $data->recipientBank        =  $resultMerchant->bankCode;
            $data->recipientAccount     =  $resultMerchant->noRek;
            $data->recipientName        =  $resultMerchant->atasNama;
            $data->amount               =  $req->amount;
            $data->note                 =  $req->note;
            $data->transactionType      =  $req->transactionType;
            $data->statusTransfer       =  "BELUM BAYAR";
            $data->spsBankNumber        =  $req->spsBankNumber;
            $data->spsBankCode          =  $req->spsBankCode;
            $data->adminFee             =  0;
            $data->totalTransfer        =  $req->amount;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertMerchant($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);
                
                // $mrc = new HijrahMerchantController;
                $response =  $doku->createdVA($data->_id);
                // $response = json_encode(array('statusCode' => '000', 'message' => "Success", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan merchant transaction"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // Cache::forget('termcontents');
        // event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }
    

    public function insertTransactionMerchantForVA(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'          => 'required',
            'idMasjid'              => 'required',
            'amount'                => 'required',
            'channel'               => 'required',
            'transactionType'       => 'required',
        ]);
        
        try {

            $data = new MerchantTransaction;

            $data->idUserMobile         =  $req->idUserMobile;
            $data->idMasjid             =  $req->idMasjid;
            $data->transactionId        =  $this->transactionId();
            $data->amount               =  $req->amount;
            $data->note                 =  $req->note;
            $data->purwantaraBankCode   =  $req->channel;
            $data->transactionType      =  $req->transactionType;
            $data->statusTransfer       =  "BELUM BAYAR";
            $data->adminFee             =  0;
            $data->totalTransfer        =  $req->amount;

            if ($data->save()) {
                $log = new LogTransactionController;
                $status = 2;
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertMerchant($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);

                $ppn = new PurwantaraController;
                $response =  $ppn->purwantaraVA($req, $data->transactionId);
                // $response = json_encode(array('statusCode' => '000', 'message' => "Success", 'data' => $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan merchant transaction"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // Cache::forget('termcontents');
        // event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Syarat Ketentuan', 'Add Syarat Ketentuan', json_decode($response)->message));
        return $response;
    }


    public function transactionId()
    {
        
        try {
            date_default_timezone_set("Asia/Jakarta");

            $start = 1;
            $dates = date('dmy');
            
            $result = MerchantTransaction::select('transactionId', 'created_at')->latest()->first();
            if ($result) { 
                if (date('dmy', strtotime($result->created_at)) == $dates) {
                    $cd = Str::substr($result, -4);
                    $start = $start+(int)$cd;
                }
            }
            $num = sprintf("%04d", $start);
            $refCd = "SPSMRC".$dates.$num;
            
            $response = $refCd;
                
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function detailDonasi($id)
    {  
        try {
            
            $mrc = new HijrahMerchantController;
            $cache = Cache::remember('donasimasjid:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($id, $mrc) {
                $data = MerchantTransaction::where('_id', $id)->first();
                if ($data == null) {
                    return null;
                }
                $arr = [
                    "_id"                   => $data->_id,
                    "idUserMobile"          => $data->idUserMobile,
                    "idMasjid"              => $data->idMasjid,
                    "nameMasjid"            => $mrc->getNameMasjid($data->idMasjid),
                    "transactionId"         => $data->transactionId,
                    "amount"                => $data->amount,
                    "note"                  => $data->note,
                    "purwantaraBankCode"    => $data->purwantaraBankCode,
                    "purwantaraBankImage"   => $mrc->bankPurwantaraImage($data->purwantaraBankCode),
                    "transactionType"       => $data->transactionType,
                    "statusTransfer"        => $data->statusTransfer,
                    "adminFee"              => $data->adminFee,
                    "recipientBank"         => $data->recipientBank,
                    "recipientAccount"      => $data->recipientAccount,
                    "recipientName"         => $data->recipientName,
                    "VANumber"              => $data->VANumber,
                    "statusVA"              => $data->statusVA
                ];
                return $arr;
            });

            if ($cache) {
                return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $cache));
            } else {
                return json_encode(array('statusCode' => '333', 'message' => "Gagal query Donasi"));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function eventDestroy($id)
    {
        try {
            LogTransaction::where('paymentId', $id)->delete();
            $delete = MerchantTransaction::where('_id', $id)->delete();
            if ($delete) {
                return json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            }else {
                return json_encode(['statusCode' => '333', 'message' => 'gagal delete']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function unikCode()
    {
        try {
            $codeUnik2  = rand(10,99);
            $codeUnik1  = rand(1,9);
            $random1 = $codeUnik1.$codeUnik2;

            $count = MerchantTransaction::where('codeUnik', $random1)->where('created_at', date("Y-m-d h:i:s"))->count();

            if ($count > 0) {
                $codeUnik3  = rand(10,99);
                $codeUnik4  = rand(1,9);
                $random2 = $codeUnik4.$codeUnik3;
                return $random2;
            }else {
                return $random1;
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
}
