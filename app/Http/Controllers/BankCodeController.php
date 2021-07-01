<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\BankCodes;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\NotificationController;
use Intervention\Image\Facades\Image as Image;
use Storage;

class BankCodeController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('onlyJson',['only'=>['getBankCode','searchBankCode']]);
    }
    
    public function getBankCode()
    {
        try {
            $key = Str::of(Cache::get('key', 'bankcodes:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('bankcodes:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                // $data = BankCodes::where('bankName', 'LIKE', '%Syariah%')->orWhere('bankName', 'LIKE', '%Muamalat%')->orderBy('bankName', 'ASC')->get();
                $data = BankCodes::whereNotNull('bankImage')->orderBy('bankName', 'ASC')->get();
                $arr_book = [];
                foreach($data as $val){
                    array_push($arr_book,[
                        '_id'           => $val->_id,
                        'bankImage'     => $val->bankImage,
                        'bankCode'      => $val->bankCode,
                        'bankName'      => $val->bankName
                    ]);
                }
                return $arr_book;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '522', 'message' => 'Data Kosong', 'data' => $cache]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }
    
    public function searchBankCode(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'bankcodes:' .$req->search. date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('bankcodes:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                $data = BankCodes::where('bankName', 'LIKE', '%' . $req->search . '%')->orderBy('bankName', 'ASC')->get();
                $arr_book = [];
                foreach($data as $val){
                    array_push($arr_book,[
                        '_id'           => $val->_id,
                        'bankImage'     => $val->bankImage,
                        'bankCode'      => $val->bankCode,
                        'bankName'      => $val->bankName
                    ]);
                }
                return $arr_book;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '522', 'message' => 'Data Kosong', 'data' => $cache]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insert(Request $req)
    {
        try {
            $validatorUrlFoto = Validator::make($req->all(), BankCodes::$rulesBankImage, BankCodes::$messages);
            $validatorFormat  = Validator::make($req->all(), BankCodes::$rulesFormatBankImage, BankCodes::$messages);
            $validatorMax     = Validator::make($req->all(), BankCodes::$rulesMaxBankImage, BankCodes::$messages);

            if ($validatorUrlFoto->fails()) {
                $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            } else {
                if ($req->hasFile('bankImage')) {

                    $files = $req->file('bankImage'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name
    
                    $filePath = '/icon_bank/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }
    
                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data = new BankCodes;

                $data->bankCode         = $req->bankCode;
                $data->bankName         = $req->bankName;
                $data->bankImage        = env('OSS_DOMAIN') . $filePath;

                if($data->save()){
                    Cache::forget('bankcodes:' . date('Y-m-d'));
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Menyimpan Bank Code"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Add Inspirasi',json_decode($response)->message));
        return $response;
    }
    
    public function update(Request $req, $id)
    {
        try {
            $validatorUrlFoto = Validator::make($req->all(), BankCodes::$rulesBankImage, BankCodes::$messages);
            $validatorFormat  = Validator::make($req->all(), BankCodes::$rulesFormatBankImage, BankCodes::$messages);
            $validatorMax     = Validator::make($req->all(), BankCodes::$rulesMaxBankImage, BankCodes::$messages);

            if ($validatorUrlFoto->fails()) {
                $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
            } else if ($validatorFormat->fails()) {
                $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
            } else if ($validatorMax->fails()) {
                $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
            } else {

                $data = BankCodes::where('_id', $id)->first();
                if ($data->bankImage != "") {
                    $ex = explode("/", $data->bankImage);
                    Storage::disk('oss')->delete('/icon_bank/' . $ex[4]);
                }

                if ($req->hasFile('bankImage')) {

                    $files = $req->file('bankImage'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name
    
                    $filePath = '/icon_bank/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }
    
                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                }

                $data->bankCode         = $req->bankCode;
                $data->bankName         = $req->bankName;
                $data->bankImage        = env('OSS_DOMAIN') . $filePath;

                if($data->save()){
                    Cache::forget('bankcodes:' . date('Y-m-d'));
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '333', 'message' => "Gagal Update Bank Code"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Add Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $data = '';
            $data = BankCodes::where('_id', $id)->delete();
            if ($data > 0) {
                Cache::forget('bankcodes:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Bank Code"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        // event(new BackOfficeUserLogEvent($this->emailUserLogin,'Inspirasi','Delete Inspirasi',json_decode($response)->message));
        return $response;
    }

    public function getBankSps()
    {
        try {
            $data = BankCodes::where('bankCode', ENV('BSI_CODE'))->get();
            $arr = [];
            foreach ($data as $value) {
                if ((int) $value->bankCode == ENV('BSI_CODE')) {
                    array_push($arr,[
                        '_id'           => $value->_id,
                        'bankCode'      => $value->bankCode,
                        'bankName'      => $value->bankName,
                        'bankImage'     => $value->bankImage,
                        'bankNumber'    => ENV('BSI_NUMBER'),
                        'spsBankName'   => ENV('BANK_NAME_SPS')
                    ]);
                }
            }
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
}
