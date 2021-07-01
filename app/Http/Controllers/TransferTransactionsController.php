<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\TransferTransactions;
use App\LogTransaction;
use App\BankCodes;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\APIEksternal\OyController;
use App\Http\Controllers\Auth\CheckValidation;

class TransferTransactionsController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLogin',['only' => ['insertPenerima','insertNominal','updateSpsBank', 'getDetailTransfer']]);
        $this->middleware('onlyJson',['only'=>['insertPenerima','insertNominal','updateSpsBank']]);
    }
    
    public function insertPenerima(Request $req)
    {
        $this->validate($req, [
            'idUserMobile'      => 'required',
            'recipientBank'     => 'required',
            'recipientAccount'  => 'required',
            'recipientName'     => 'required',
        ]);
        try {
            $oy = new OyController;
            
            $data = new TransferTransactions;

            $data->idUserMobile     = $req->idUserMobile;
            $data->recipientBank    = $req->recipientBank;
            $data->recipientAccount = $req->recipientAccount;
            $data->recipientName    = $req->recipientName;
            $data->transferId       = $oy->partnerTrxId();

            if ($data->save()) {
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Penerima Transfer']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function insertNominal(Request $req, $id)
    {
        $this->validate($req, [
            'nominal'   => 'required',
        ]);
        try {
            $check = new CheckValidation;
            $minMax = json_decode($check->minMaxTransfer($req));
            if ($minMax->statusCode == '773' || $minMax->statusCode == '778') {
                return $check->minMaxTransfer($req);
            }else {
                $data = TransferTransactions::where('_id', $id)->first();

            
                $data->nominal      = $req->nominal;
                $data->note         = $req->note;
    
                if ($data->save()) {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                }else {
                    return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Penerima Transfer']);
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function tigaCodeUnik()
    {
        try {
            $codeUnik2  = rand(10,100);
            $codeUnik1  = rand(1,2);
            $random1 = $codeUnik1.$codeUnik2;

            $count = TransferTransactions::where('codeUnik', $random1)->where('created_at', date("Y-m-d h:i:s"))->count();

            if ($count > 0) {
                $codeUnik3  = rand(10,100);
                $codeUnik4  = rand(1,2);
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

    public function tigaCodeUnikPlus()
    {
        try {
            $random1  = rand(1,200);

            $count = TransferTransactions::where('codeUnik', $random1)->where('created_at', date("Y-m-d h:i:s"))->count();

            if ($count > 0) {
                $random2  = rand(1,200);
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

    public function cekBiayaAdmin($bankCode, $nominal)
    {
        try {
            $admin = 0;
            if ($bankCode == env('SYARIAH_BNI_CODE') || $bankCode == env('SYARIAH_BRI_CODE') || $bankCode == env('SYARIAH_MANDIRI_CODE') || $bankCode == env('BSI_CODE')) {
                $admin = $admin + env('ADMIN_FEE_OY');
                return $admin;
            }else {
                $admin = $admin + ENV("ADMIN_DIFFERENT") + env('ADMIN_FEE_OY');
                return $admin;
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function getDetailTransfer($id)
    {
        try {

            $data       = TransferTransactions::where('_id', $id)->first();
            if ($data == null) {
                return json_encode(['statusCode' => '444', 'message' => 'ID Transfer Tidak ada']);
            }
            $codeBank   = BankCodes::where('bankCode', $data->recipientBank)->first();
            $code       = BankCodes::where('bankCode', $data->spsBankCode)->first();
            $arr = [
                '_id'                   => $data->_id,
                'idUserMobile'          => $data->idUserMobile,
                'transferId'            => $data->transferId,
                'trxId'                 => $data->trxId,
                'recipientBank'         => $data->recipientBank,
                'recipientBankName'     => $codeBank->bankName,
                'recipientBankImage'    => $codeBank->bankImage,
                'recipientAccount'      => $data->recipientAccount,
                'recipientName'         => $data->recipientName,
                'amount'                => $data->amount,
                'note'                  => $data->note,
                'statusTransfer'        => $data->statusTransfer,
                'codeUnik'              => $data->codeUnik,
                'adminFee'              => $data->adminFee,
                'spsBankCode'           => $data->spsBankCode,
                'spsBank'               => $data->spsBank,
                'spsBankName'           => $code->bankName,
                'spsBankImage'          => $code->bankImage,
                'spsBankName'           => ENV('BANK_NAME_SPS'),
                'nominal'               => $data->nominal
            ];
            if ($arr) {
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error get data']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }  
    public function updateSpsBank(Request $req, $id)
    {
        $this->validate($req, [
            'spsBankCode'   => 'required',
            'spsBankNumber' => 'required',
        ]);
        try {

            $data = TransferTransactions::where('_id', $id)->first();
            $admin = $this->cekBiayaAdmin($data->recipientBank, $data->nominal);
            $codeUnik = 0;
            if ($admin == 0) {
                $codeUnik   = $codeUnik + $this->tigaCodeUnikPlus();
                $total      = (int) $data->nominal + $admin + $codeUnik;
            }else {
                $codeUnik   = $codeUnik + $this->tigaCodeUnik();
                $total      = (int) $data->nominal + $admin - $codeUnik;
            }

            $data->spsBankCode  = $req->spsBankCode;
            $data->spsBank      = $req->spsBankNumber;
            $data->codeUnik     = $codeUnik;
            $data->adminFee     = $admin;
            $data->amount       = $total;

            if ($data->save()) {
                $log = new LogTransactionController;
                $status = 2;
                $desc = "Transfer ke ".$data->recipientName;
                $log->insertOyTransfer($data->_id, $data->idUserMobile, $data->transferId, $data->amount,$status, $desc);
                
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Penerima Transfer']);
            }

        } catch (\Exception $e) {
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
            $delete = TransferTransactions::where('_id', $id)->delete();
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
