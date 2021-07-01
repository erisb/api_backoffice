<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\LogTransaction;
use App\Helpers\FormatDate;

class LogTransactionController extends Controller
{
    private $token, $emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLogin', ['except' => ['hitoryTransaction']]);
        // $this->middleware('authLoginBackOffice', ['only' => ['umrohRelease']]);
        // $this->middleware('onlyJson', ['only' => ['umrohCreate', 'umrohPay', 'umrohStock', 'umrohRelease', 'insertRoom', 'updateUmroh', 'searchCity', 'searchPrice', 'searchDate', 'package', 'transactionHistory', 'addCarts', 'destroyCarts', 'getCart', 'notifikasi']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token', $this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id', $token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function insertOyTransfer($id, $idUserMobile,$transferId, $price, $status, $description)
    {
        try {
            $log = LogTransaction::where('paymentId', $id)->count();
            if ($log > 0) {
                $data = LogTransaction::where('paymentId', $id)->first();

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 2;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }else {
                $data = new LogTransaction;

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 2;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertPpobMp($id, $idUserMobile,$transferId, $price, $status, $description)
    {
        try {
            $log = LogTransaction::where('paymentId', $id)->count();
            if ($log > 0) {
                $data = LogTransaction::where('paymentId', $id)->first();

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 3;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }else {
                $data = new LogTransaction;

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 3;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertSetorTarik($id, $idUserMobile,$transferId, $price, $status, $description)
    {
        try {
            $log = LogTransaction::where('paymentId', $id)->count();
            if ($log > 0) {
                $data = LogTransaction::where('paymentId', $id)->first();

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 4;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }else {
                $data = new LogTransaction;

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 4;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertMerchant($id, $idUserMobile,$transferId, $price, $status, $description)
    {
        try {
            $log = LogTransaction::where('paymentId', $id)->count();
            if ($log > 0) {
                $data = LogTransaction::where('paymentId', $id)->first();

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 5;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }else {
                $data = new LogTransaction;

                $data->bookingCode          = $transferId;
                $data->idUserMobile         = $idUserMobile;
                $data->totalPrice           = $price;
                $data->paymentId            = $id;
                $data->paymentStatus        = $status;
                $data->description          = $description;
                $data->flag                 = 5;

                if ($data->save()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    Cache::forget('notification:' . date('Y-m-d').$idUserMobile);
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '999', 'message' => 'Error save Notification']);
                }
            }
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function hitoryTransaction($id)
    {
        try {
            $data = LogTransaction::where('idUserMobile', $id)->orderBy('created_at', 'DESC')->get();

            $arr = [];
            foreach ($data as $value) {
                array_push($arr,[
                    '_id'           => $value->_id,
                    'bookingCode'   => $value->bookingCode,
                    'idUserMobile'  => $value->idUserMobile,
                    'totalPrice'    => $value->totalPrice,
                    'paymentId'     => $value->paymentId,
                    'paymentStatus' => $value->paymentStatus,
                    'description'   => $value->description,
                    'flag'          => $value->flag,
                    'created_at'    => FormatDate::stringToDate(($value->created_at)),
                    'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                ]);
            }
            
            if ($arr) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

    }

    public function hitoryTransactionHome(Request $req)
    {
        try {
            $data = LogTransaction::where('idUserMobile', $req->idUserMobile)->orderBy('created_at', 'DESC')->take(3)->get();
            $arr = [];
            foreach ($data as $value) {
                array_push($arr,[
                    '_id'           => $value->_id,
                    'bookingCode'   => $value->bookingCode,
                    'idUserMobile'  => $value->idUserMobile,
                    'totalPrice'    => $value->totalPrice,
                    'paymentId'     => $value->paymentId,
                    'paymentStatus' => $value->paymentStatus,
                    'description'   => $value->description,
                    'flag'          => $value->flag,
                    'created_at'    => FormatDate::stringToDate(($value->created_at)),
                    'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                ]);
            }
            
            if ($arr) {
                return $arr;
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function searchHitoryTransaction(Request $req)
    {
        try {
            $data = LogTransaction::where('description','LIKE', '%'.$req->search.'%')->orderBy('created_at', 'DESC')->get();

            $arr = [];
            foreach ($data as $value) {
                if ($value->idUserMobile == $req->idUserMobile) {
                    array_push($arr,[
                        '_id'           => $value->_id,
                        'bookingCode'   => $value->bookingCode,
                        'idUserMobile'  => $value->idUserMobile,
                        'totalPrice'    => $value->totalPrice,
                        'paymentId'     => $value->paymentId,
                        'paymentStatus' => $value->paymentStatus,
                        'description'   => $value->description,
                        'flag'          => $value->flag,
                        'created_at'    => FormatDate::stringToDate(($value->created_at)),
                        'updated_at'    => FormatDate::stringToDate(($value->updated_at)),
                    ]);
                }
            }
            
            if ($arr) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

    }
}