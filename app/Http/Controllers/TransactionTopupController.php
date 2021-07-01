<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\TransactionTopup;
use App\LogTransaction;
use App\BankCodes;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\APIEksternal\MobilePulsaController;
use App\Http\Controllers\APIEksternal\PurwantaraController;
use App\Http\Controllers\LogTransactionController;

class TransactionTopupController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLogin',['only' => ['insertPraBayar','insertPascaBayar', 'getTransactionTopup']]);
        $this->middleware('onlyJson',['only'=>['insertPraBayar','insertPascaBayar']]);
    }
    
    public function insertPraBayar(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'  => 'required',
            'hp'            => 'required',
            'pulsa_code'    => 'required',
            'pulsa_op'      => 'required',
            'pulsa_nominal' => 'required',
            'pulsa_price'   => 'required',
            'pulsa_type'    => 'required',
            'pulsa_details' => 'required',
            'masaaktif'     => 'required',
        ]);
        try {
            $codeRef = new MobilePulsaController;
            $unikCode = $this->cekCodeUnik($req->pulsa_price);
            $adminFee = ENV('MOBILEPULSA_FEE') - $unikCode;
            $total = (int) $req->pulsa_price + $adminFee;
            $data = new TransactionTopup;

            $data->idUserMobile     = $req->idUserMobile;
            $data->trnsactionId     = null;
            $data->refId            = $codeRef->refCode();
            $data->spsBank          = env('BSI_NUMBER');
            $data->hp               = $req->hp;
            $data->codeTopup        = $req->pulsa_code;
            $data->operatorTopup    = $req->pulsa_op;
            $data->nominalTopup     = $req->pulsa_nominal;
            $data->priceTopup       = $req->pulsa_price;
            $data->typeTopup        = $req->pulsa_type;
            $data->detailTopup      = $req->pulsa_details;
            $data->masaAktif        = $req->masaaktif;
            $data->noReferensi      = null;
            $data->messageTopup     = "BELUM BAYAR";
            $data->balanceTopup     = null;
            $data->serialNumber     = null;
            $data->statusTopup      = null;
            $data->trName           = null;
            $data->period           = null;
            $data->admin            = (int) ENV('MOBILEPULSA_FEE');
            $data->sellingPrice     = null;
            $data->desc             = null;
            $data->datetime         = null;
            $data->mpType           = "PRA";
            $data->codeUnik         = $unikCode;
            $data->totalTransfer    = $total;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;

                if($data->typeTopup == 'data'){
                    $nominalTopup = (int) $data->nominalTopup;
                    $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }else if($data->typeTopup == 'etoll'){
                    if ($data->detailTopup == '-' || $data->operatorTopup == 'GoPay E-Money') {
                        $getNominal = substr($data->nominalTopup, (int) strpos($data->nominalTopup, "Rp ") + 3);
                        $nominalTopup = (int)str_replace(".", "", $getNominal);
                        $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                    }else {
                        $desc = $data->detailTopup;
                    }
                }else {
                    $nominalTopup = (int) $data->nominalTopup;
                    $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }                
                $log->insertPpobMp($data->_id, $data->idUserMobile, $data->refId, $data->totalTransfer,$status, $desc);
                
                if ($data->typeTopup == "pln") {
                    $pln = new MobilePulsaController;
                    $dataPln = json_decode($pln->inquiryPrepaidPln($data->hp));
                    if ($dataPln->data->status == 1) {
                        $arr = [
                            '_id' => $data->_id,
                            'idUserMobile' => $data->idUserMobile,
                            'trnsactionId' => $data->trnsactionId,
                            'refId' => $data->refId,
                            'spsBank' => $data->spsBank,
                            'hp' => $data->hp,
                            'codeTopup' => $data->codeTopup,
                            'operatorTopup' => $data->operatorTopup,
                            'nominalTopup' => $data->nominalTopup,
                            'priceTopup' => $data->priceTopup,
                            'typeTopup' => $data->typeTopup,
                            'detailTopup' => $data->detailTopup,
                            'masaAktif' => $data->masaAktif,
                            'noReferensi' => $data->noReferensi,
                            'messageTopup' => $data->messageTopup,
                            'balanceTopup' => $data->balanceTopup,
                            'serialNumber' => $dataPln->data->meter_no,
                            'statusTopup' => $data->statusTopup,
                            'trName' => $dataPln->data->name,
                            'period' => $data->period,
                            'admin' => $data->admin,
                            'sellingPrice' => $data->sellingPrice,
                            'desc' => $dataPln->data->segment_power,
                            'datetime' => $data->datetime,
                            'mpType' => $data->mpType,
                            'codeUnik' => $data->codeUnik,
                            'totalTransfer' => $data->totalTransfer,
                            'created_at' => $data->created_at,
                            'updated_at' => $data->updated_at,
                        ];
                        return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
                    }else {
                        TransactionTopup::where('_id', $data->_id)->delete();
                        LogTransaction::where('_id', $data->_id)->delete();
                        return json_encode(['statusCode' => '888', 'message' => $dataPln->data->message]);
                    }
                    
                }else {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                }
                
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Topup Pra']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function insertPascaBayar(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'  => 'required',
            'hp'            => 'required',
            'tr_id'         => 'required',
            'ref_id'        => 'required',
            'code'          => 'required',
            'nominal'       => 'required',
            'price'         => 'required',
            'tr_name'       => 'required',
            'selling_price' => 'required',
        ]);
        try {
            $purwantara = new PurwantaraController;
            $count = TransactionTopup::where('trnsactionId', $req->tr_id)->count();
            if ($count > 0) {
                return json_encode(['statusCode' => '431', 'message' => 'Trnsaction Id sudah digunakan']);
            }
            
            $unikCode = $this->cekCodeUnik($req->selling_price);
            $adminFee = ENV('MOBILEPULSA_FEE') - $unikCode;
            $total = (int) $req->selling_price + $adminFee;

            $data = new TransactionTopup;

            $data->idUserMobile     = $req->idUserMobile;
            $data->trnsactionId     = $req->tr_id;
            $data->refId            = $req->ref_id;
            $data->spsBank          = env('BSI_NUMBER');
            $data->hp               = $req->hp;
            $data->codeTopup        = $req->code;
            $data->operatorTopup    = null;
            $data->nominalTopup     = (string)$req->nominal;
            $data->priceTopup       = (string)$req->price;
            $data->typeTopup        = null;
            $data->detailTopup      = null;
            $data->masaAktif        = null;
            $data->noReferensi      = null;
            $data->messageTopup     = "BELUM BAYAR";
            $data->balanceTopup     = null;
            $data->serialNumber     = null;
            $data->statusTopup      = null;
            $data->trName           = $req->tr_name;
            $data->period           = $req->period;
            $data->admin            = (int) $req->admin + ENV('MOBILEPULSA_FEE');
            $data->sellingPrice     = $req->selling_price;
            $data->desc             = null;
            $data->datetime         = null;
            $data->mpType           = "PASCA";
            $data->codeUnik         = $unikCode;
            $data->totalTransfer    = $total;

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 1;
                $desc = "Pembayaran a/n ".  $data->trName. " Rp. " .number_format($data->nominalTopup, 2);
                $log->insertPpobMp($data->_id, $data->idUserMobile, $data->refId, $data->totalTransfer,$status, $desc);
                
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Topup Pasca']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }


    // ======================================== Topup with Qris ======================================
    
    public function insertPraBayarQris(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'  => 'required',
            'hp'            => 'required',
            'pulsa_code'    => 'required',
            'pulsa_op'      => 'required',
            'pulsa_nominal' => 'required',
            'pulsa_price'   => 'required',
            'pulsa_type'    => 'required',
            'pulsa_details' => 'required',
            'masaaktif'     => 'required',
            'paymentType'   => 'required',
        ]);
        try {
            $purwantara = new PurwantaraController;
            $codeRef = new MobilePulsaController;
            $adminFee = ENV('MOBILEPULSA_FEE');
            $total = (int) $req->pulsa_price + $adminFee;
            $data = new TransactionTopup;

            $data->idUserMobile     = $req->idUserMobile;
            $data->trnsactionId     = null;
            $data->refId            = $codeRef->refCode();
            $data->hp               = $req->hp;
            $data->codeTopup        = $req->pulsa_code;
            $data->operatorTopup    = $req->pulsa_op;
            $data->nominalTopup     = $req->pulsa_nominal;
            $data->priceTopup       = (string)$req->pulsa_price;
            $data->typeTopup        = $req->pulsa_type;
            $data->detailTopup      = $req->pulsa_details;
            $data->masaAktif        = $req->masaaktif;
            $data->noReferensi      = null;
            $data->messageTopup     = "BELUM BAYAR";
            $data->balanceTopup     = null;
            $data->serialNumber     = null;
            $data->statusTopup      = null;
            $data->trName           = null;
            $data->period           = null;
            $data->admin            = (int) ENV('MOBILEPULSA_FEE');
            $data->sellingPrice     = null;
            $data->desc             = null;
            $data->datetime         = null;
            $data->mpType           = "PRA";
            $data->totalTransfer    = $total;
            $data->paymentType      = $req->paymentType; // OVO atau shopeepay

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 2;

                if($data->typeTopup == 'data'){
                    $nominalTopup = (int) $data->nominalTopup;
                    $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }else if($data->typeTopup == 'etoll'){
                    if ($data->detailTopup == '-' || $data->operatorTopup == 'GoPay E-Money') {
                        $getNominal = substr($data->nominalTopup, (int) strpos($data->nominalTopup, "Rp ") + 3);
                        $nominalTopup = (int)str_replace(".", "", $getNominal);
                        $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                    }else {
                        $desc = $data->detailTopup;
                    }
                }else {
                    $nominalTopup = (int) $data->nominalTopup;
                    $desc = "Topup ".$data->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }                
                $log->insertPpobMp($data->_id, $data->idUserMobile, $data->refId, $data->totalTransfer,$status, $desc);
                
                if ($data->typeTopup == "pln") {
                    $pln = new MobilePulsaController;
                    $dataPln = json_decode($pln->inquiryPrepaidPln($data->hp));
                    if ($dataPln->data->status == 1) {
                        $arr = [
                            '_id' => $data->_id,
                            'idUserMobile' => $data->idUserMobile,
                            'trnsactionId' => $data->trnsactionId,
                            'refId' => $data->refId,
                            'spsBank' => $data->spsBank,
                            'hp' => $data->hp,
                            'codeTopup' => $data->codeTopup,
                            'operatorTopup' => $data->operatorTopup,
                            'nominalTopup' => $data->nominalTopup,
                            'priceTopup' => $data->priceTopup,
                            'typeTopup' => $data->typeTopup,
                            'detailTopup' => $data->detailTopup,
                            'masaAktif' => $data->masaAktif,
                            'noReferensi' => $data->noReferensi,
                            'messageTopup' => $data->messageTopup,
                            'balanceTopup' => $data->balanceTopup,
                            'serialNumber' => $dataPln->data->meter_no,
                            'statusTopup' => $data->statusTopup,
                            'trName' => $dataPln->data->name,
                            'period' => $data->period,
                            'admin' => $data->admin,
                            'sellingPrice' => $data->sellingPrice,
                            'desc' => $dataPln->data->segment_power,
                            'datetime' => $data->datetime,
                            'mpType' => $data->mpType,
                            'codeUnik' => $data->codeUnik,
                            'totalTransfer' => $data->totalTransfer,
                            'created_at' => $data->created_at,
                            'updated_at' => $data->updated_at,
                        ];
                        return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
                    }else {
                        TransactionTopup::where('_id', $data->_id)->delete();
                        LogTransaction::where('_id', $data->_id)->delete();
                        return json_encode(['statusCode' => '888', 'message' => $dataPln->data->message]);
                    }
                    
                }else {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                }
                
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Topup Pra']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function insertPascaBayarQris(Request $req)
    {
        
        $this->validate($req, [
            'idUserMobile'  => 'required',
            'hp'            => 'required',
            'tr_id'         => 'required',
            'ref_id'        => 'required',
            'code'          => 'required',
            'nominal'       => 'required',
            'price'         => 'required',
            'tr_name'       => 'required',
            'selling_price' => 'required',
            'paymentType'   => 'required',
        ]);
        try {
            $count = TransactionTopup::where('trnsactionId', $req->tr_id)->count();
            if ($count > 0) {
                return json_encode(['statusCode' => '431', 'message' => 'Trnsaction Id sudah digunakan']);
            }
            
            $adminFee = ENV('MOBILEPULSA_FEE');
            $total = (int) $req->selling_price + $adminFee;

            $data = new TransactionTopup;

            $data->idUserMobile     = $req->idUserMobile;
            $data->trnsactionId     = $req->tr_id;
            $data->refId            = $req->ref_id;
            $data->hp               = $req->hp;
            $data->codeTopup        = $req->code;
            $data->operatorTopup    = null;
            $data->nominalTopup     = (string)$req->nominal;
            $data->priceTopup       = (string)$req->price;
            $data->typeTopup        = null;
            $data->detailTopup      = null;
            $data->masaAktif        = null;
            $data->noReferensi      = null;
            $data->messageTopup     = "BELUM BAYAR";
            $data->balanceTopup     = null;
            $data->serialNumber     = null;
            $data->statusTopup      = null;
            $data->trName           = $req->tr_name;
            $data->period           = $req->period;
            $data->admin            = (int) $req->admin + ENV('MOBILEPULSA_FEE');
            $data->sellingPrice     = $req->selling_price;
            $data->desc             = null;
            $data->datetime         = null;
            $data->mpType           = "PASCA";
            $data->totalTransfer    = $total;
            $data->paymentType      = $req->paymentType;  // OVO atau shopeepay

            if ($data->save()) {
                
                $log = new LogTransactionController;
                $status = 1;
                $desc = "Pembayaran a/n ".  $data->trName. " Rp. " .number_format($data->nominalTopup, 2);
                $log->insertPpobMp($data->_id, $data->idUserMobile, $data->refId, $data->totalTransfer,$status, $desc);
                
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                return json_encode(['statusCode' => '461', 'message' => 'Error Save Data Topup Pasca']);
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
            $codeUnik2  = rand(10,99);
            $codeUnik1  = rand(3,4);
            $random1 = $codeUnik1.$codeUnik2;

            $count = TransactionTopup::where('codeUnik', $random1)->where('created_at', date("Y-m-d h:i:s"))->count();

            if ($count > 0) {
                $codeUnik3  = rand(10,99);
                $codeUnik4  = rand(3,4);
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

    public function cekCodeUnik($price)
    {
        try {
            $admin = $this->tigaCodeUnik();
            return $admin;

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function getTransactionTopup($id)
    {
        try {

            $data = TransactionTopup::where('_id', $id)->first();
            if ($data) {
                if ($data->mpType == "PRA" && $data->typeTopup == "pln" && ($data->messageTopup != "BELUM BAYAR" && $data->messageTopup != "PROCESS")) {
                    $a = explode("/",$data->serialNumber);
                    $volt = $a[2]."/".$a[3]."/".$a[4];
                    $arr = [
                        '_id' => $data->_id,
                        'idUserMobile' => $data->idUserMobile,
                        'trnsactionId' => $data->trnsactionId,
                        'refId' => $data->refId,
                        'spsBank' => $data->spsBank,
                        'hp' => $data->hp,
                        'codeTopup' => $data->codeTopup,
                        'operatorTopup' => $data->operatorTopup,
                        'nominalTopup' => $data->nominalTopup,
                        'priceTopup' => $data->priceTopup,
                        'typeTopup' => $data->typeTopup,
                        'detailTopup' => $data->detailTopup,
                        'masaAktif' => $data->masaAktif,
                        'noReferensi' => $data->noReferensi,
                        'messageTopup' => $data->messageTopup,
                        'balanceTopup' => $data->balanceTopup,
                        'serialNumber' => $a[0],
                        'statusTopup' => $data->statusTopup,
                        'trName' => $a[1],
                        'period' => $data->period,
                        'admin' => $data->admin,
                        'sellingPrice' => $data->sellingPrice,
                        'desc' => $volt,
                        'datetime' => $data->datetime,
                        'mpType' => $data->mpType,
                        'codeUnik' => $data->codeUnik,
                        'totalTransfer' => $data->totalTransfer,
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ];
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
                }else {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                }
            }else {
                return json_encode(['statusCode' => '337', 'message' => 'silahkan cek kembali id']);
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
            $delete = TransactionTopup::where('_id', $id)->delete();
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
