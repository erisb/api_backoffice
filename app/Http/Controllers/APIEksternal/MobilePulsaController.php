<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\TransactionTopup;
use App\ReferensiCode;
use App\Http\Controllers\LogTransactionController;


class MobilePulsaController extends Controller

{
    private $token, $emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('authLogin', ['only' => ['listInquiryBpjsKesehatan', 'listInquiryPdam', 'listInquiryPlnPasca', 'listInquiryTelepon']]);
        $this->middleware('onlyJson', ['only' => ['mobileListPdamByProvince', 'listInquiryBpjsKesehatan', 'listInquiryPdam', 'listInquiryPlnPasca', 'listInquiryTelepon']]);

    }

    // UNUTK PRA BAYAR
    public function mobileListPulsa()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/pulsa", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListData()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/data", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListEtoll()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListDana()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll/dana", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
    public function mobileListShopeePay()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll/shopee_pay", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
    public function mobileListOvo()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll/ovo", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
    public function mobileListLinkAja()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll/linkaja", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
    public function mobileListGopay()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/etoll/gopay_e-money", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListPln()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'status'    => 'active'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA')."/pln", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function inquiryPrepaidPln($hp)
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$hp);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inquiry_pln',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'hp'        => $hp,
                'sign'      => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA'), $send)->getBody()->getContents();

            $response = $data;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListTopUp($id)
    {
        try {
            
            $dataTrc = TransactionTopup::where('_id', $id)->first();

            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$dataTrc->refId);
            $client = new Client();

            $body = json_encode([
                'commands'      => 'topup',
                'username'      => env('USERNAME_MOBILEPULSA'),
                'ref_id'        => $dataTrc->refId,
                "hp"            => $dataTrc->hp,
                "pulsa_code"    => $dataTrc->codeTopup,
                'sign'          => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->status == 0) {

                $dataTrc->trnsactionId  = $result->data->tr_id;
                $dataTrc->refId         = $result->data->ref_id;
                $dataTrc->hp            = $result->data->hp;
                $dataTrc->codeTopup     = $result->data->code;
                $dataTrc->priceTopup    = (string)$result->data->price;
                $dataTrc->messageTopup  = $result->data->message;
                $dataTrc->balanceTopup  = $result->data->balance;
                $dataTrc->statusTopup   = $result->data->status;
                $dataTrc->mpType        = "PRA";

                if ($dataTrc->save()) {
                
                    $log = new LogTransactionController;
                    $status = "";
                    if ($dataTrc->messageTopup == "SUCCESS") {
                        $status = 0;
                    }else if ($dataTrc->messageTopup == "BELUM BAYAR" || $dataTrc->messageTopup == "PROCESS") {
                        $status = 2;
                    }else {
                        $status = 1;
                    }
                    $desc = "Topup ".$dataTrc->typeTopup. " Rp. " .number_format($dataTrc->nominalTopup, 2);
                    $log->insertPpobMp($dataTrc->_id, $dataTrc->idUserMobile, $dataTrc->refId, $dataTrc->totalTransfer,$status, $desc);
                    
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $dataTrc]);
                }else {
                    $response = json_encode(['statusCode' => '379', 'message' => 'Error insert data Topup']);
                }
            }else {
                $response = json_encode(['statusCode' => '610', 'message' => 'Error Data', 'data' => $result->data->message]);
            }
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function refCode()
    {
        
        try {
            date_default_timezone_set("Asia/Jakarta");

            $start = 1;
            $dates = date('dmy');
            
            $result = ReferensiCode::select('referensiCode', 'created_at')->latest()->first();
            if ($result) { 
                if (date('dmy', strtotime($result->created_at)) == $dates) {
                    $cd = Str::substr($result, -4);
                    $start = $start+(int)$cd;
                }
            }
            $num = sprintf("%04d", $start);
            $refCd = "SPS".$dates.$num;

            $insert = new ReferensiCode;

            $insert->referensiCode = $refCd;
            if ($insert->save()) {
                $response = $refCd;
            }
                
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListCheckStatusTransaksi(Request $req)
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$req->referenceCode);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inquiry',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'ref_id'    => $req->referenceCode,
                'sign'      => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            
            $topup = TransactionTopup::where('refId', $req->referenceCode)->first();

            $topup->messageTopup    = $result->data->message;
            $topup->statusTopup     = $result->data->rc;

            if ($topup->save()) {
                $log = new LogTransactionController;
                $status = "";
                if ($topup->messageTopup == "SUCCESS") {
                    $status = 0;
                }else{
                    $status = 1;
                }
                $desc = "Topup ".$topup->typeTopup. " Rp. " .number_format($topup->nominalTopup, 2);
                $log->insertPpobMp($topup->_id, $topup->idUserMobile, $topup->refId, $topup->totalTransfer,$status, $desc);
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $topup]);
            }else {
                $response = json_encode(['statusCode' => '961', 'message' => 'Error update data Topup']);
            }
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }


    // UNUTK PASCA BAYAR
    public function mobileListBpjs()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA')."/bpjs", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data->pasca]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListPdam()
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA')."/pdam", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data->pasca]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileListPdamByProvince(Request $req)
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."pl");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pricelist-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'sign'      => $sign,
                'province'  => $req->province,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA')."/pdam", $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data->pasca]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function listInquiryBpjsKesehatan(Request $req)
    {
        
        $this->validate($req, [
            'hp'    => 'required',
        ]);
        try {
            $code = $this->refCode();
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$code);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inq-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'code'      => "BPJS",
                'hp'        => $req->hp,
                'ref_id'    => $code,
                'sign'      => $sign,
                'month'     => $req->month,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->response_code == "00") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
            }else {
                $response = json_encode(['statusCode' => '414', 'message' => $result->data->message]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function listInquiryPdam(Request $req)
    {
        
        $this->validate($req, [
            'code'  => 'required',
            'hp'    => 'required',
        ]);
        try {
            $code = $this->refCode();
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$code);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inq-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'code'      => $req->code,
                'hp'        => $req->hp,
                'ref_id'    => $code,
                'sign'      => $sign,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->response_code == "00") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
            }else {
                $response = json_encode(['statusCode' => '414', 'message' => $result->data->message]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function listInquiryPlnPasca(Request $req)
    {
        
        $this->validate($req, [
            'hp'    => 'required',
        ]);

        try {
            $code = $this->refCode();
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$code);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inq-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'code'      => 'PLNPOSTPAID',
                'hp'        => $req->hp,
                'ref_id'    => $code,
                'sign'      => $sign,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->response_code == "00") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
            }else {
                $response = json_encode(['statusCode' => '414', 'message' => $result->data->message]);
            }
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function listInquiryTelepon(Request $req)
    {
        
        $this->validate($req, [
            'hp'    => 'required',
        ]);

        try {
            
            $sub = substr($req->hp,0,4);
            $typeCode = "";
            if ($sub == '0811' || $sub == '0812' || $sub == '0813' || $sub == '0821' || $sub == '0822') {
                $typeCode = 'HPTSEL';
            }else if ($sub == '0814' || $sub == '0815' || $sub == '0816' || $sub == '0855' || $sub == '0858') {
                $typeCode = 'HPMTRIX';
            }else if ($sub == '0817' || $sub == '0818' || $sub == '0819' || $sub == '0859' || $sub == '0877' || $sub == '0878') {
                $typeCode = 'HPXL';
            }else if ($sub == '0895' || $sub == '0896' || $sub == '0897' || $sub == '0898' || $sub == '0899') {
                $typeCode = 'HPTHREE';
            }else{
                $typeCode = 'TELKOMPSTN';
            }
            
            $code = $this->refCode();
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$code);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'inq-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'code'      => $typeCode,
                'hp'        => $req->hp,
                'ref_id'    => $code,
                'sign'      => $sign,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->response_code == "00") {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
            }else {
                $response = json_encode(['statusCode' => '414', 'message' => $result->data->message]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobilePayPasca($id)
    {
        try {
            $tans = TransactionTopup::where('_id', $id)->first();
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA').$tans->trnsactionId);
            $client = new Client();

            $body = json_encode([
                'commands'  => 'pay-pasca',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'tr_id'     => $tans->trnsactionId,
                'sign'      => $sign,
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            if ($result->data->response_code == "00") {

                $tans->trnsactionId = $result->data->tr_id;
                $tans->refId        = $result->data->ref_id;
                $tans->codeTopup    = $result->data->code;
                $tans->hp           = $result->data->hp;
                $tans->trName       = $result->data->tr_name;
                $tans->period       = $result->data->period;
                $tans->nominalTopup = (string)$result->data->nominal;
                $tans->datetime     = $result->data->datetime;
                $tans->admin        = $result->data->admin;
                $tans->messageTopup = $result->data->message;
                $tans->statusTopup  = (string) $result->data->response_code;
                $tans->priceTopup   = $result->data->price;
                $tans->sellingPrice = (string)$result->data->selling_price;
                $tans->balanceTopup = $result->data->balance;
                $tans->noReferensi  = $result->data->noref;
                $tans->desc         = $result->data->desc;
                $tans->mpType       = "PASCA";
                $tans->serialNumber = "";

                if ($tans->save()) {
                    $log = new LogTransactionController;
                    $status = 0;
                    $desc = "Pembayaran a/n ".  $tans->trName. " Rp. " .number_format((int)$tans->nominalTopup, 2);
                    $log->insertPpobMp($tans->_id, $tans->idUserMobile, $tans->refId, $tans->totalTransfer,$status, $desc);
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $tans]);
                }else {
                    $response = json_encode(['statusCode' => '379', 'message' => 'Error insert data transaksi']);
                }
            } else if ($result->data->response_code == "05" || $result->data->response_code == "39" || $result->data->response_code == "201") {
                
                $tans->trnsactionId = $result->data->tr_id;
                $tans->refId        = $result->data->ref_id;
                $tans->codeTopup    = $result->data->code;
                $tans->hp           = $result->data->hp;
                $tans->trName       = $result->data->tr_name;
                $tans->period       = $result->data->period;
                $tans->nominalTopup = (string)$result->data->nominal;
                $tans->datetime     = $result->data->datetime;
                $tans->admin        = $result->data->admin;
                $tans->messageTopup = $result->data->message;
                $tans->statusTopup  = (string) $result->data->response_code;
                $tans->priceTopup   = $result->data->price;
                $tans->sellingPrice = (string)$result->data->selling_price;
                $tans->balanceTopup = $result->data->balance;
                $tans->noReferensi  = $result->data->noref;
                $tans->desc         = $result->data->desc;
                $tans->mpType       = "PASCA";
                $tans->serialNumber = "";

                if ($tans->save()) {
                    $response = json_encode(['statusCode' => '101', 'message' => 'Pembayaran dalam proses', 'data' => $tans]);
                }else {
                    $response = json_encode(['statusCode' => '379', 'message' => 'Error insert data transaksi']);
                }
            } else if ($result->data->response_code == "01") {
                $response = json_encode(['statusCode' => '301', 'message' => $result->data->message]);
            } else {
                
                $tans->trnsactionId = $result->data->tr_id;
                $tans->refId        = $result->data->ref_id;
                $tans->codeTopup    = $result->data->code;
                $tans->hp           = $result->data->hp;
                $tans->trName       = $result->data->tr_name;
                $tans->period       = $result->data->period;
                $tans->nominalTopup = (string)$result->data->nominal;
                $tans->datetime     = $result->data->datetime;
                $tans->admin        = $result->data->admin;
                $tans->messageTopup = $result->data->message;
                $tans->statusTopup  = (string) $result->data->response_code;
                $tans->priceTopup   = $result->data->price;
                $tans->sellingPrice = (string)$result->data->selling_price;
                $tans->balanceTopup = $result->data->balance;
                $tans->noReferensi  = $result->data->noref;
                $tans->desc         = $result->data->desc;
                $tans->mpType       = "PASCA";
                $tans->serialNumber = "";

                if ($tans->save()) {
                    $log = new LogTransactionController;
                    $status = 1;
                    $desc = "Pembayaran a/n ".  $tans->trName. " Rp. " .number_format((int)$tans->nominalTopup, 2);
                    $log->insertPpobMp($tans->_id, $tans->idUserMobile, $tans->refId, $tans->totalTransfer,$status, $desc);
                    $response = json_encode(['statusCode' => '312', 'message' => 'Filed', 'data' => $tans]);
                }else {
                    $response = json_encode(['statusCode' => '379', 'message' => 'Error insert data transaksi']);
                }
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function mobileCheckStatusTransaksiPasca(Request $req)
    {
        try {
            $sign = md5(env('USERNAME_MOBILEPULSA').env('API_KEY_MOBILEPULSA')."cs");
            $client = new Client();

            $body = json_encode([
                'commands'  => 'checkstatus',
                'username'  => env('USERNAME_MOBILEPULSA'),
                'ref_id'    => $req->referenceCode,
                'sign'      => $sign
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];

            $data = $client->post(env('API_MOBILEPULSA_PASCA'), $send)->getBody()->getContents();

            $result = json_decode($data);
            $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result->data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    

    public function callbackPrePaid(Request $req)
    {
        try {
            $dataTrc = TransactionTopup::where('refId', $req->data['ref_id'])->first(); 

            if ($req->data['rc'] == "00") {
                $dataTrc->serialNumber  = $req->data['sn'];
                $dataTrc->messageTopup  = $req->data['message'];
                $dataTrc->statusTopup   = $req->data['rc'];
            }else {
                $dataTrc->messageTopup  = $req->data['message'];
                $dataTrc->statusTopup   = $req->data['rc'];
            }

            if ($dataTrc->save()) {
                $log = new LogTransactionController;
                $status = "";
                if ($dataTrc->messageTopup == "SUCCESS") {
                    $status = 0;
                }else{
                    $status = 1;
                }

                if($dataTrc->typeTopup == 'data'){
                    $nominalTopup = (int) $dataTrc->nominalTopup;
                    $desc = "Topup ".$dataTrc->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }else if($dataTrc->typeTopup == 'etoll'){
                    if ($dataTrc->detailTopup == '-' || $dataTrc->operatorTopup == 'GoPay E-Money') {
                        $getNominal = substr($dataTrc->nominalTopup, (int) strpos($dataTrc->nominalTopup, "Rp ") + 3);
                        $nominalTopup = (int)str_replace(".", "", $getNominal);
                        $desc = "Topup ".$dataTrc->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                    }else {
                        $desc = $dataTrc->detailTopup;
                    }
                }else {
                    $nominalTopup = (int) $dataTrc->nominalTopup;
                    $desc = "Topup ".$dataTrc->typeTopup. " Rp. " .number_format($nominalTopup, 2);
                }     
                $log->insertPpobMp($dataTrc->_id, $dataTrc->idUserMobile, $dataTrc->refId, $dataTrc->totalTransfer,$status, $desc);
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $dataTrc]);
            }else {
                $response = json_encode(['statusCode' => '379', 'message' => 'Error insert data Topup']);
            }
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function transactionFromMoota($id)
    {
        try {
            $data = TransactionTopup::where('_id', $id)->first();

            if ($data->trnsactionId != "") {
                $response = $this->mobilePayPasca($id);
            }else{
                $response = $this->mobileListTopUp($id);
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