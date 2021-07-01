<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\CashTransactions;
use App\UserMobiles;
use App\LogTransaction;
use App\Events\CacheFlushEvent;
use App\Events\CashTransactionEvent;
use App\Events\BackOfficeUserLogEvent;
use App\Helpers\FormatDate;
use App\Http\Controllers\APIEksternal\OyController;

class CashTransactionController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLoginBackOffice',['only' => ['insert','update','destroy']]);
        $this->middleware('onlyJson',['only'=>['insertSetor', 'insertTarik']]);
        // $this->token = request()->token;
        // $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        // $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        // $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function insertSetor(Request $req)
    {
        try {
            $total  = (int) $req->amount - (int) env('ADMIN_FEE_SETOR');
            $user   = UserMobiles::where('_id', $req->idUserMobile)->first();
            $data = new CashTransactions;

            $data->idUserMobile     =  $req->idUserMobile;
            $data->transactionId    =  $this->cashTrxId();
            $data->recipientBank    =  $req->recipientBank;
            $data->recipientAccount =  $req->recipientAccount;
            $data->recipientName    =  $req->recipientName;
            $data->recvPhoneNumber  =  $user->noTelpUser;
            $data->amount           =  $req->amount;
            $data->transactionType  =  $req->transactionType;
            $data->channel          =  $req->channel;
            $data->status           =  "BELUM BAYAR";
            $data->adminFee         =  (int) env('ADMIN_FEE_SETOR');
            $data->totalTransaksi   =  $total;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertSetorTarik($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);
                
                $oy = new OyController;
                $response = $oy->inOutMoney($data->_id);
                // $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", "data" => $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function insertTarik(Request $req)
    {
        try {
            $unik = $this->tigaCodeUnik();
            $total = ((int) $req->amount + (int) env('ADMIN_FEE_TARIK')) - $unik;
            $user   = UserMobiles::where('_id', $req->idUserMobile)->first();
            $data = new CashTransactions;

            $data->idUserMobile     =  $req->idUserMobile;
            $data->transactionId    =  $this->cashTrxId();
            $data->spsBankCode      =  $req->spsBankCode;
            $data->spsBank          =  $req->spsBank;
            $data->recvPhoneNumber  =  $user->noTelpUser;
            $data->amount           =  $req->amount;
            $data->transactionType  =  $req->transactionType;
            $data->channel          =  $req->channel;
            $data->status           =  "BELUM BAYAR";
            $data->adminFee         =  (int) env('ADMIN_FEE_TARIK');
            $data->codeUnik         =  $unik;
            $data->totalTransaksi   =  $total;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;
                $desc = $data->transactionType . " Rp. " .number_format($data->amount, 2);
                $log->insertSetorTarik($data->_id, $data->idUserMobile, $data->transactionId, $data->amount,$status, $desc);
                
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", "data" => $data));
            } else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Kategori"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function cashTrxId()
    {
        try {
            date_default_timezone_set("Asia/Jakarta");

            $start = 1;
            $dates = date('dmy');
            
            $result = CashTransactions::select('transactionId', 'created_at')->latest()->first();
            if ($result) { 
                if (date('dmy', strtotime($result->created_at)) == $dates) {
                    $cd = Str::substr($result, -4);
                    $start = $start+(int)$cd;
                }
            }
            $num = sprintf("%04d", $start);
            $refCd = "SPSTRX".$dates.$num;
            
            $response = $refCd;
                
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function tigaCodeUnik()
    {
        try {
            $codeUnik2  = rand(10,99);
            $codeUnik1  = rand(1,4);
            $random1 = $codeUnik1.$codeUnik2;

            $count = CashTransactions::where('codeUnik', $random1)->where('created_at', date("Y-m-d h:i:s"))->count();

            if ($count > 0) {
                $codeUnik3  = rand(10,99);
                $codeUnik4  = rand(1,4);
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
    
    public function detaiSetorTarik($id)
    {
        try {
            $data = CashTransactions::where('_id', $id)->first();
            if ($data) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", "data" => $data));
            }else {
                $response = json_encode(array('statusCode' => '333', 'message' => "Data Kosong"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function eventDestroy($id)
    {
        try {
            LogTransaction::where('paymentId', $id)->delete(); 
            $delete = CashTransactions::where('_id', $id)->delete();
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
}
