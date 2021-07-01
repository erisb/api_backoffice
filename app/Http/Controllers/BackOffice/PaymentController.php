<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UmrohOrder;
use App\UserMobiles;
use App\LogTransaction;
use App\Events\BackOfficeUserLogEvent;
use App\Http\Controllers\Mail\HijrahEmailController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\APIEksternal\PergiUmrohController;

class PaymentController extends Controller
{
    private $token, $emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice', ['only' => ['approvedPayment', 'rejectedPayment', 'pembayaran']]);
        $this->middleware('onlyJson',['only'=>['approvedPayment','rejectedPayment','searchPayment','pembayaran']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token', $this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id', $token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function listPaymentFirstPage($take)
    {
        try {
            $data = UmrohOrder::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = UmrohOrder::count();
            $arr = [];
            foreach ($data as $value) {
                $user = UserMobiles::where('_id', $value->idUserMobile)->first();
                array_push($arr, [
                    '_id' => $value->_id,
                    'idUserMobile' => $value->idUserMobile,
                    'nama' => $user != null ? $user->namaUser : '',
                    'codeBooking' => $value->bookingCode,
                    'orderCode' => $value->orderCode,
                    'orderId' => $value->orderId,
                    'listPayment' => $value->listPayment,
                    'departureDate' => $value->departureDate,
                    'totalAmount' => $value->totalPrice,
                    'status' => $value->status,
                    'isCancel' => $value->isCancel
                ]);
            }

            if ($arr) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr, 'total' => $totalData]);
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

    public function listPaymentByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $data = UmrohOrder::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = UmrohOrder::count();
            $arr = [];
            foreach ($data as $value) {
                $user = UserMobiles::where('_id', $value->idUserMobile)->first();
                array_push($arr, [
                    '_id' => $value->_id,
                    'idUserMobile' => $value->idUserMobile,
                    'nama' => $user != null ? $user->namaUser : '',
                    'codeBooking' => $value->bookingCode,
                    'orderCode' => $value->orderCode,
                    'orderId' => $value->orderId,
                    'listPayment' => $value->listPayment,
                    'departureDate' => $value->departureDate,
                    'totalAmount' => $value->totalPrice,
                    'status' => $value->status,
                    'isCancel' => $value->isCancel
                ]);
            }

            if ($arr) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $arr, 'total' => $totalData]);
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

    public function approvedPayment(Request $req)
    {
        try {
            $mail       = new HijrahEmailController;
            $umroh      = new PergiUmrohController;
            $notif      = new NotificationController;
            $data       = UmrohOrder::where('_id', $req->orderId)->first();
            $arr        = [];
            $count      = count($data->listPayment);
            $index      = 0;
            $price      = 0;
            $dateNow    = date("Y-m-d h:i:sa");
            for ($i = 0; $i < $count; $i++) {
                if ($data->listPayment[$i]['paymentId'] == $req->paymentId) {
                    $index = $i;
                    $price = $data->listPayment[$i]['billed'];
                    array_push($arr, [
                        'paymentId'     => $data->listPayment[$i]['paymentId'],
                        'description'   => 'Lunas',
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => 0,
                        'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar'],
                        'paymentDate'   => $dateNow
                    ]);
                } else {
                    array_push($arr, [
                        'paymentId'     => $data->listPayment[$i]['paymentId'],
                        'description'   => $data->listPayment[$i]['description'],
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => $data->listPayment[$i]['status'],
                        'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar'],
                        'paymentDate'   => $data->listPayment[$i]['paymentDate']
                    ]);
                }
            }
            $data->listPayment = $arr;

            if ($data->update()) {
                $umroh->updateStatus($req->orderId);
                $mail->approvalMail($req);
                $umroh->umrohPay($req);

                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = $price;
                $log->paymentId     = $req->paymentId;
                $log->paymentStatus = 0;
                if ($count > 1) {
                    if ($index == 0) {
                        $log->description   = 'Pembayaran DP';
                    } else if ($index == 1) {
                        $log->description   = 'Pembayaran ke-2';
                    } else if ($index == 2) {
                        $log->description   = 'Pembayaran ke-3';
                    }
                } else {
                    $log->description   = 'Pembayaran Penuh';
                }

                $log->flag          = 1;

                if ($log->save()) {
                    if ($count > 1) {
                        if ($index == 0) {
                            $position       = 3;
                            $description    = 'Pembayaran ke-2 Rp. ' . number_format($data->listPayment[1]['billed'], 2, ',', '.');
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 1) {
                            $position       = 3;
                            $description    = 'Pembayaran ke-3 Rp. ' . number_format($data->listPayment[2]['billed'], 2, ',', '.');
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 2) {
                            $ret = 0;
                            for ($i = 0; $i < count($data->listPilgrims); $i++) {
                                if (empty($data->listPilgrims[$i]['urlKtp'])) {
                                    $ret = 0;
                                } else if (empty($data->listPilgrims[$i]['urlKK'])) {
                                    $ret = 0;
                                } else if (empty($data->listPilgrims[$i]['urlBukuNikah'])) {
                                    $ret = 0;
                                } else if (empty($data->listPilgrims[$i]['urlBukuMiningitis'])) {
                                    $ret = 0;
                                } else {
                                    $ret = 1;
                                }
                            }
                            if ($ret == 1) {
                                $position       = 4;
                                $description    = 'Pembayaran Lunas';
                                $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                            } else {
                                $position       = 3;
                                $description    = 'Data Belum Lengkap';
                                $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                            }
                        }
                    } else {
                        $ret = 0;
                        for ($i = 0; $i < count($data->listPilgrims); $i++) {
                            if (empty($data->listPilgrims[$i]['urlKtp'])) {
                                $ret = 0;
                            } else if (empty($data->listPilgrims[$i]['urlKK'])) {
                                $ret = 0;
                            } else if (empty($data->listPilgrims[$i]['urlBukuNikah'])) {
                                $ret = 0;
                            } else if (empty($data->listPilgrims[$i]['urlBukuMiningitis'])) {
                                $ret = 0;
                            } else {
                                $ret = 1;
                            }
                        }
                        if ($ret == 1) {
                            $position       = 4;
                            $description    = 'Pembayaran Lunas';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else {
                            $position       = 3;
                            $description    = 'Data Belum Lengkap';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        }
                    }
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses', 'data' => $data, 'logTransaction' => $log));
                } else {
                    $response = json_encode(array('statusCode' => '856', 'message' => 'Error save log transaksi'));
                }
            } else {
                $response = json_encode(array('message' => 'Kosong', 'data' => null));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Approved', 'Approved - ' . $req->orderId, json_decode($response)->message));
        return $response;
    }

    public function rejectedPayment(Request $req)
    {
        try {
            $mail = new HijrahEmailController;
            $umroh = new PergiUmrohController;
            $notif = new NotificationController;
            $data = UmrohOrder::where('_id', $req->orderId)->first();
            $arr = [];
            $count = count($data->listPayment);
            $index = 0;
            $price = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($data->listPayment[$i]['paymentId'] == $req->paymentId) {
                    $index = $i;
                    $price = $data->listPayment[$i]['billed'];
                    array_push($arr, [
                        'paymentId'     => $data->listPayment[$i]['paymentId'],
                        'description'   => 'Rejected',
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => 9,
                        'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar'],
                        'paymentDate'   => $data->listPayment[$i]['paymentDate']
                    ]);
                } else {
                    array_push($arr, [
                        'paymentId'     => $data->listPayment[$i]['paymentId'],
                        'description'   => $data->listPayment[$i]['description'],
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => $data->listPayment[$i]['status'],
                        'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar'],
                        'paymentDate'   => $data->listPayment[$i]['paymentDate']
                    ]);
                }
            }

            $data->listPayment = $arr;

            if ($data->update()) {
                $umroh->updateStatus($req->orderId);
                $mail->rejectedMail($req);

                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = $price;
                $log->paymentId    = $req->paymentId;
                $log->paymentStatus = 4;
                if ($count > 1) {
                    if ($index == 0) {
                        $log->description   = 'Pembayaran DP';
                    } else if ($index == 1) {
                        $log->description   = 'Pembayaran ke-2';
                    } else if ($index == 2) {
                        $log->description   = 'Pembayaran ke-3';
                    }
                } else {
                    $log->description   = 'Pembayaran Penuh';
                }

                $log->flag          = 1;

                if ($log->save()) {
                    if ($count > 1) {
                        if ($index == 0) {
                            $position       = 3;
                            $description    = 'Pembayaran Dp di Tolak';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 1) {
                            $position       = 3;
                            $description    = 'Pembayaran ke-2 di Tolak';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 2) {
                            $position       = 3;
                            $description    = 'Pembayaran ke-3 di Tolak';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        }
                    } else {
                        $position       = 3;
                        $description    = 'Pembayaran  di Tolak';
                        $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                    }
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses', 'data' => $data, 'logTransaction' => $log));
                } else {
                    $response = json_encode(array('statusCode' => '856', 'message' => 'Error save log transaksi'));
                }
            } else {
                $response = json_encode(array('message' => 'Kosong', 'data' => null));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Rejected', 'Rejected - ' . $req->orderId, json_decode($response)->message));
        return $response;
    }

    public function searchPayment(Request $req)
    {
        try {
            $val = str_replace(' ', '', $req->search);
            $umroh = UmrohOrder::where('bookingCode', 'like', '%' . $val . '%')->with(['user_mobiles'])->get();
            $user = UserMobiles::where('namaUser', 'like', '%' . $val . '%')->with(['umroh_orders'])->get();

            if (count($umroh) != 0) {
                $arr = [];
                foreach ($umroh as $value) {
                    array_push($arr, [
                        '_id' => $value->_id,
                        'idUserMobile' => $value->idUserMobile,
                        'nama' => $value->user_mobiles->namaUser,
                        'codeBooking' => $value->bookingCode,
                        'orderCode' => $value->orderCode,
                        'orderId' => $value->orderId,
                        'listPayment' => $value->listPayment,
                        'departureDate' => $value->departureDate,
                        'totalAmount' => $value->totalPrice,
                        'status' => $value->status,
                        'isCancel' => $value->isCancel
                    ]);
                }
            } else if (count($user) != 0) {
                $arr = [];
                for ($i = 0; $i < count($user[0]->umroh_orders); $i++) {
                    array_push($arr, [
                        '_id' => $user[0]->umroh_orders[$i]->_id,
                        'idUserMobile' => $user[0]->umroh_orders[$i]->idUserMobile,
                        'nama' => $user[0]->namaUser,
                        'codeBooking' => $user[0]->umroh_orders[$i]->bookingCode,
                        'orderCode' => $user[0]->umroh_orders[$i]->orderCode,
                        'orderId' => $user[0]->umroh_orders[$i]->orderId,
                        'listPayment' => $user[0]->umroh_orders[$i]->listPayment,
                        'departureDate' => $user[0]->umroh_orders[$i]->departureDate,
                        'totalAmount' => $user[0]->umroh_orders[$i]->totalPrice,
                        'status' => $user[0]->umroh_orders[$i]->status,
                        'isCancel' => $user[0]->umroh_orders[$i]->isCancel
                    ]);
                }
            } else {
                $arr = [
                    'message' => 'Kosong',
                    'data' => null
                ];
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

    public function pembayaran(Request $req)
    {
        try {

            $mail   = new HijrahEmailController;
            $umroh  = new PergiUmrohController;
            $notif  = new NotificationController;
            $data   = UmrohOrder::where('_id', $req->orderId)->first();
            $dateNow    = date("Y-m-d h:i:sa");
            if ((int) $req->bayar >= $data->totalPrice) {
                $payArr = [
                    [
                        'paymentId'     => $data->listPayment[0]['paymentId'],
                        'description'   => 'Lunas',
                        'payment_info'  => $data->listPayment[0]['payment_info'],
                        'due_date'      => $data->listPayment[0]['due_date'],
                        'billed'        => $data->listPayment[0]['billed'],
                        'status'        => 0,
                        'urlBuktiBayar' => $data->listPayment[0]['urlBuktiBayar'],
                        'paymentDate'   => $dateNow
                    ],
                    [
                        'paymentId'     => $data->listPayment[1]['paymentId'],
                        'description'   => 'Lunas',
                        'payment_info'  => $data->listPayment[1]['payment_info'],
                        'due_date'      => $data->listPayment[1]['due_date'],
                        'billed'        => $data->listPayment[1]['billed'],
                        'status'        => 0,
                        'urlBuktiBayar' => $data->listPayment[1]['urlBuktiBayar'],
                        'paymentDate'   => $dateNow
                    ],
                    [
                        'paymentId'     => $data->listPayment[2]['paymentId'],
                        'description'   => 'Lunas',
                        'payment_info'  => $data->listPayment[2]['payment_info'],
                        'due_date'      => $data->listPayment[2]['due_date'],
                        'billed'        => $data->listPayment[2]['billed'],
                        'status'        => 0,
                        'urlBuktiBayar' => $data->listPayment[2]['urlBuktiBayar'],
                        'paymentDate'   => $dateNow
                    ]
                ];
            } else {
                if ($data->listPayment[0]['paymentId'] == $req->paymentId) {
                    $dp         = (int) $data->totalPrice - (int) $req->bayar;
                    $cicilan    = $dp / 2;
                    $payArr = [
                        [
                            'paymentId'     => $data->listPayment[0]['paymentId'],
                            'description'   => 'Lunas',
                            'payment_info'  => $data->listPayment[0]['payment_info'],
                            'due_date'      => $data->listPayment[0]['due_date'],
                            'billed'        => (int) $req->bayar,
                            'status'        => 0,
                            'urlBuktiBayar' => $data->listPayment[0]['urlBuktiBayar'],
                            'paymentDate'   => $dateNow
                        ],
                        [
                            'paymentId'     => $data->listPayment[1]['paymentId'],
                            'description'   => $data->listPayment[1]['description'],
                            'payment_info'  => $data->listPayment[1]['payment_info'],
                            'due_date'      => $data->listPayment[1]['due_date'],
                            'billed'        => $cicilan,
                            'status'        => $data->listPayment[1]['status'],
                            'urlBuktiBayar' => $data->listPayment[1]['urlBuktiBayar'],
                            'paymentDate'   => $data->listPayment[1]['paymentDate']
                        ],
                        [
                            'paymentId'     => $data->listPayment[2]['paymentId'],
                            'description'   => $data->listPayment[2]['description'],
                            'payment_info'  => $data->listPayment[2]['payment_info'],
                            'due_date'      => $data->listPayment[2]['due_date'],
                            'billed'        => $cicilan,
                            'status'        => $data->listPayment[2]['status'],
                            'urlBuktiBayar' => $data->listPayment[2]['urlBuktiBayar'],
                            'paymentDate'   => $data->listPayment[2]['paymentDate']
                        ]
                    ];
                } elseif ($data->listPayment[1]['paymentId'] == $req->paymentId) {

                    $jumlah = $data->listPayment[1]['billed'] + $data->listPayment[2]['billed'];
                    $total = $jumlah - (int) $req->bayar;

                    if ((int) $req->bayar >= (int) $jumlah) {
                        $payArr = [
                            [
                                'paymentId'     => $data->listPayment[0]['paymentId'],
                                'description'   => $data->listPayment[0]['description'],
                                'payment_info'  => $data->listPayment[0]['payment_info'],
                                'due_date'      => $data->listPayment[0]['due_date'],
                                'billed'        => $data->listPayment[0]['billed'],
                                'status'        => $data->listPayment[0]['status'],
                                'urlBuktiBayar' => $data->listPayment[0]['urlBuktiBayar'],
                                'paymentDate'   => $data->listPayment[0]['paymentDate']
                            ],
                            [
                                'paymentId'     => $data->listPayment[1]['paymentId'],
                                'description'   => 'Lunas',
                                'payment_info'  => $data->listPayment[1]['payment_info'],
                                'due_date'      => $data->listPayment[1]['due_date'],
                                'billed'        => $data->listPayment[1]['billed'],
                                'status'        => 0,
                                'urlBuktiBayar' => $data->listPayment[1]['urlBuktiBayar'],
                                'paymentDate'   => $dateNow
                            ],
                            [
                                'paymentId'     => $data->listPayment[2]['paymentId'],
                                'description'   => 'Lunas',
                                'payment_info'  => $data->listPayment[2]['payment_info'],
                                'due_date'      => $data->listPayment[2]['due_date'],
                                'billed'        => $data->listPayment[2]['billed'],
                                'status'        => 0,
                                'urlBuktiBayar' => $data->listPayment[2]['urlBuktiBayar'],
                                'paymentDate'   => $dateNow
                            ]
                        ];
                    } else {
                        $payArr = [
                            [
                                'paymentId'     => $data->listPayment[0]['paymentId'],
                                'description'   => $data->listPayment[0]['description'],
                                'payment_info'  => $data->listPayment[0]['payment_info'],
                                'due_date'      => $data->listPayment[0]['due_date'],
                                'billed'        => $data->listPayment[0]['billed'],
                                'status'        => $data->listPayment[0]['status'],
                                'urlBuktiBayar' => $data->listPayment[0]['urlBuktiBayar'],
                                'paymentDate'   => $data->listPayment[0]['paymentDate']
                            ],
                            [
                                'paymentId'     => $data->listPayment[1]['paymentId'],
                                'description'   => 'Lunas',
                                'payment_info'  => $data->listPayment[1]['payment_info'],
                                'due_date'      => $data->listPayment[1]['due_date'],
                                'billed'        => (int) $req->bayar,
                                'status'        => 0,
                                'urlBuktiBayar' => $data->listPayment[1]['urlBuktiBayar'],
                                'paymentDate'   => $dateNow
                            ],
                            [
                                'paymentId'     => $data->listPayment[2]['paymentId'],
                                'description'   => $data->listPayment[2]['description'],
                                'payment_info'  => $data->listPayment[2]['payment_info'],
                                'due_date'      => $data->listPayment[2]['due_date'],
                                'billed'        => $total,
                                'status'        => $data->listPayment[2]['status'],
                                'urlBuktiBayar' => $data->listPayment[2]['urlBuktiBayar'],
                                'paymentDate'   => $data->listPayment[2]['paymentDate']
                            ]
                        ];
                    }
                }
            }

            $data->listPayment = $payArr;

            if ($data->update()) {
                $umroh->updateStatus($req->orderId);
                $mail->approvalMail($req);
                $umroh->umrohPay($req);


                $count = count($data->listPayment);
                $index = 0;

                for ($i = 0; $i < $count; $i++) {
                    if ($data->listPayment[$i]['paymentId'] == $req->paymentId) {
                        $index = $i;
                    }
                }
                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = (int) $req->bayar;
                $log->paymentId    = $req->paymentId;
                $log->paymentStatus = 0;
                if ($count > 1) {
                    if ($index == 0) {
                        $log->description   = 'Pembayaran DP';
                    } else if ($index == 1) {
                        $log->description   = 'Pembayaran ke-2';
                    } else if ($index == 2) {
                        $log->description   = 'Pembayaran ke-3';
                    }
                } else {
                    $log->description   = 'Pembayaran Penuh';
                }

                $log->flag          = 1;

                if ($log->save()) {
                    if ($count > 1) {
                        if ($index == 0) {
                            $priceNotif     = $data->listPayment[1]['billed'];
                            $position       = 3;
                            $description    = 'Pembayaran ke-2 Rp. ' . number_format($priceNotif, 2, ',', '.');
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 1) {
                            $priceNotif     = $data->listPayment[2]['billed'];
                            $position       = 3;
                            $description    = 'Pembayaran ke-3 Rp. ' . number_format($priceNotif, 2, ',', '.');
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        } else if ($index == 2) {
                            $position       = 4;
                            $description    = 'Pembayaran Lunas';
                            $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                        }
                    } else {
                        $position       = 4;
                        $description    = 'Pembayaran Lunas';
                        $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);
                    }
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses', 'data' => $data, 'logTransaction' => $log));
                } else {
                    $response = json_encode(array('statusCode' => '856', 'message' => 'Error save log transaksi'));
                }
            } else {
                $response = json_encode(array('message' => 'Kosong', 'data' => null));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Adjustment', 'Adjustment - ' . $req->orderId, json_decode($response)->message));
        return $response;
    }
}
