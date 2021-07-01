<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;
use App\Events\PergiUmrohEvent;
use App\Events\UmrohTokenEvent;
use App\Events\DueDateUmrohEvent;
use App\Events\NotificationEvent;
use App\UmrohOrder;
use App\HijrahCarts;
use App\UmrohPackage;
use App\UmrohToken;
use App\Events\BackOfficeUserLogEvent;
use App\LogTransaction;
use App\Http\Controllers\NotificationController;
use Intervention\Image\Facades\Image as Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Storage;
use DateTime;

class PergiUmrohController extends Controller
{
    private $token, $emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLogin', ['except' => ['authenticationLogin', 'packageInsert', 'nextStap', 'package', 'packageHome', 'packageById', 'generate', 'pembayaranPenuh', 'searchCity', 'searchDate', 'searchPrice', 'listPayment', 'pembayaranKredit', 'pembayaranPenuh', 'promosion', 'syaratKetentuan', 'umrohRelease']]);
        $this->middleware('authLoginBackOffice', ['only' => ['umrohRelease']]);
        $this->middleware('onlyJson', ['only' => ['umrohCreate', 'umrohPay', 'umrohStock', 'umrohRelease', 'insertRoom', 'updateUmroh', 'searchCity', 'searchPrice', 'searchDate', 'package', 'transactionHistory', 'addCarts', 'destroyCarts', 'getCart', 'notifikasi']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token', $this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id', $token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function authenticationLogin()
    {
        try {
            $client = new Client();

            $body = json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];

            $data = $client->post(env('API_PERGI_UMROH') . 'login', $send)->getBody()->getContents();

            $token = json_decode($data);

            $umroh = new UmrohToken;

            $umroh->token_type      = $token->token_type;
            $umroh->expires_in      = $token->expires_in;
            $umroh->access_token    = $token->access_token;

            $count = umrohToken::count();
            if ($count > 0) {
                umrohToken::truncate();
            }

            if ($umroh->save()) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $umroh]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function packageInsert()
    {
        try {
            date_default_timezone_set("Asia/Jakarta");

            $client = new Client();

            $token = UmrohToken::latest()->first();

            $body = json_encode([
                // 'month' => 'client_credentials',
                // 'departure_from' => 'Departure From',
                // 'limit' => 4,
                // 'offset' => 10,
            ]);
            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];

            $data = $client->post(env('API_PERGI_UMROH') . 'packages', $send)->getBody()->getContents();

            $length = json_decode($data);

            $arr = [];
            for ($i = 0; $i < count($length->data); $i++) {

                //for array roms
                $arr_rooms = [];
                $arr_hotels = [];
                $arr_itineraries = [];
                for ($a = 0; $a < count($length->data[$i]->rooms); $a++) {
                    array_push($arr_rooms, [
                        'type' => $length->data[$i]->rooms[$a]->type,
                        'price' => $length->data[$i]->rooms[$a]->price
                    ]);
                }

                for ($h = 0; $h < count($length->data[$i]->hotels); $h++) {
                    array_push($arr_hotels, [
                        'id' => $length->data[$i]->hotels[$h]->id,
                        'name' => $length->data[$i]->hotels[$h]->name,
                        'rating' => $length->data[$i]->hotels[$h]->rating,
                        'distance' => $length->data[$i]->hotels[$h]->distance,
                        'distance_meter' => $length->data[$i]->hotels[$h]->distance_meter,
                        'distance_to' => $length->data[$i]->hotels[$h]->distance_to,
                        'gallery' => $length->data[$i]->hotels[$h]->gallery,
                        'website' => $length->data[$i]->hotels[$h]->website,
                        'latitude' => $length->data[$i]->hotels[$h]->latitude,
                        'longitude' => $length->data[$i]->hotels[$h]->longitude,
                        'check_in' => $length->data[$i]->hotels[$h]->check_in,
                        'check_out' => $length->data[$i]->hotels[$h]->check_out,
                    ]);
                }

                for ($t = 0; $t < count($length->data[$i]->itineraries); $t++) {
                    array_push($arr_itineraries, [
                        'days' => $length->data[$i]->itineraries[$t]->days,
                        'description' => $length->data[$i]->itineraries[$t]->description,
                    ]);
                }

                array_push($arr, [
                    'id' => $length->data[$i]->id,
                    'image' => $length->data[$i]->image,
                    'name' => $length->data[$i]->name,
                    'description' => $length->data[$i]->description,
                    'travel_id' => $length->data[$i]->travel_id,
                    'travel_name' => $length->data[$i]->travel_name,
                    'travel_avatar' => $length->data[$i]->travel_avatar,
                    'travel_umrah_permission' => $length->data[$i]->travel_umrah_permission,
                    'travel_description' => $length->data[$i]->travel_description,
                    'travel_address' => $length->data[$i]->travel_address,
                    'travel_pilgrims' => $length->data[$i]->travel_pilgrims,
                    'travel_founded' => $length->data[$i]->travel_founded,
                    'stock' => $length->data[$i]->stock,
                    'duration' => $length->data[$i]->duration,
                    'departure_date' => $length->data[$i]->departure_date,
                    'available_seat' => $length->data[$i]->available_seat,
                    'original_price' => $length->data[$i]->original_price,
                    'reduced_price' => $length->data[$i]->reduced_price,
                    'discount' => $length->data[$i]->discount,
                    'departure_from' => $length->data[$i]->departure_from,
                    'transit' => $length->data[$i]->transit,
                    'arrival_city' => $length->data[$i]->arrival_city,
                    'origin_arrival_city' => $length->data[$i]->origin_arrival_city,
                    'departure_city' => $length->data[$i]->departure_city,
                    'origin_departure_city' => $length->data[$i]->origin_departure_city,
                    'down_payment' => $length->data[$i]->down_payment,
                    'rooms' => $arr_rooms,
                    'airlines' => [
                        'departure' => [
                            'id' => $length->data[$i]->airlines->departure->id,
                            'name' => $length->data[$i]->airlines->departure->name,
                            'logo' => $length->data[$i]->airlines->departure->logo,
                        ],
                        'return' => [
                            'id' => $length->data[$i]->airlines->departure->id,
                            'name' => $length->data[$i]->airlines->departure->name,
                            'logo' => $length->data[$i]->airlines->departure->logo,
                        ]
                    ],
                    'hotels' => $arr_hotels,
                    'itineraries' => $arr_itineraries,
                    'is_change_package' => $length->data[$i]->is_change_package,
                    'notes' => $length->data[$i]->notes,
                    'is_dummy' => $length->data[$i]->is_dummy,
                    'created_at' => date("Y-m-d h:i:sa")
                ]);
            }

            $count = UmrohPackage::count();
            if ($count > 0) {
                if (UmrohPackage::truncate()) {
                    $umrohPackage = UmrohPackage::insert($arr);
                }
            } else {
                $umrohPackage = UmrohPackage::insert($arr);
            }

            $response = $umrohPackage;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function packageHome()
    {
        
        try {
            $client = new Client();

            $token = UmrohToken::latest()->first();

            $body = json_encode([
                // 'month' => 'client_credentials',
                // 'departure_from' => 'Departure From',
                'limit' => 4,
                // 'offset' => 10,
            ]);
            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];

            $data = $client->post(env('API_PERGI_UMROH') . 'packages', $send)->getBody()->getContents();
            
            $decodeData = json_decode($data);
            $response = json_encode($decodeData->data);
        } catch (RequestException $e) {
            
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
            
        }
        
        return $response;
    }

    public function packageById($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'packageDetail:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $send = [
                'headers'   => [
                    'http_errors' => false,
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $validate = UmrohPackage::where('id', $id)->first();
            if ($validate == null) {
                return response()->json(['statusCode' => '444', 'message' => 'Periksa Kembali ID nya']);
            }
            $client = new Client();
            $cache = Cache::remember('packageDetail:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($client, $send, $id) {
                $result = json_decode($client->get(env('API_PERGI_UMROH') . 'package/' . $id, $send)->getBody()->getContents());
                $hotel[] = $result->data->hotels[0]->gallery;
                $arrImg = [];
                if ($hotel[0] != '') {
                    foreach ($hotel[0] as $image) {
                        array_push($arrImg, array('imageHotel' => $image));
                    }
                }
                for ($i = 0; $i <= count($hotel); $i++) {
                    $arrHotel[] = [
                        'id' => $result->data->hotels[$i]->id,
                        'name' => $result->data->hotels[$i]->name,
                        'rating' => $result->data->hotels[$i]->rating,
                        'distance_meter' => $result->data->hotels[$i]->distance_meter,
                        'gallery' => $arrImg,
                        'website' => $result->data->hotels[$i]->website,
                        'latitude' => $result->data->hotels[$i]->latitude,
                        'longitude' => $result->data->hotels[$i]->longitude,
                        'check_in' => $result->data->hotels[$i]->check_in,
                        'check_out' => $result->data->hotels[$i]->check_out,
                    ];
                }
                $facilitiesInc[] = $result->data->facilities[0];
                $arrFacInc = [];
                if ($facilitiesInc != '') {
                    foreach ($facilitiesInc[0] as $facInc) {
                        array_push($arrFacInc, array('description' => $facInc));
                    }
                }

                $facilitiesExc[] = $result->data->facilities[0];
                $arrFacExc = [];
                if ($facilitiesExc != '') {
                    foreach ($facilitiesExc[0] as $facExc) {
                        array_push($arrFacExc, array('description' => $facExc));
                    }
                }

                $arrFacilities = [
                    'include' => $arrFacInc,
                    'exclude' => $arrFacExc
                ];

                $arrBaru = [
                    'id' => $result->data->id,
                    'image' => $result->data->image,
                    'name' => $result->data->name,
                    'description' => $result->data->description,
                    'travel_id' => $result->data->travel_id,
                    'travel_name' => $result->data->travel_name,
                    'travel_avatar' => $result->data->travel_avatar,
                    'travel_description' => $result->data->travel_description,
                    'travel_address' => $result->data->travel_address,
                    'travel_pilgrims' => $result->data->travel_pilgrims,
                    'travel_umrah_permission' => $result->data->travel_umrah_permission,
                    'travel_founded' => $result->data->travel_founded,
                    'stock' => $result->data->stock,
                    'duration' => $result->data->duration,
                    'departure_date' => $result->data->departure_date,
                    'available_seat' => $result->data->available_seat,
                    'original_price' => $result->data->original_price,
                    'reduced_price' => $result->data->reduced_price,
                    'discount' => $result->data->discount,
                    'departure_from' => $result->data->departure_from,
                    'transit' => $result->data->transit,
                    'arrival_city' => $result->data->arrival_city,
                    'departure_city' => $result->data->departure_city,
                    'origin_departure_city' => $result->data->origin_departure_city,
                    'origin_arrival_city' => $result->data->origin_arrival_city,
                    'down_payment' => $result->data->down_payment,
                    'rooms' => $result->data->rooms,
                    'airlines' => $result->data->airlines,
                    'hotels' => $arrHotel,
                    'facilities' => $arrFacilities,
                    'itineraries' => $result->data->itineraries,
                    'is_change_package' => $result->data->is_change_package,
                    'notes' => $result->data->notes
                ];

                return $arrBaru;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function umrohCreate(Request $req, $id)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohCreate:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $notif = new NotificationController;

            $data = UmrohOrder::where('_id', $id)->first();


            for ($i = 0; $i < $data->totalPilgrims; $i++) {
                $pilgrims[] = [
                    'first_name' => $data->listPilgrims[$i]['nama'],
                    'date_of_birth' => $data->listPilgrims[$i]['tglLahir']
                ];
            }

            if ($req->payMethod == 1) {
                $payments = $this->pembayaranKredit($id);
            } else if ($req->payMethod == 2) {
                $payments = $this->pembayaranPenuh($id);
            }

            $body = json_encode([
                'package_id' => $data->packageId,
                'room' => $data->room,
                'booking_code' => $data->bookingCode,
                'pilgrims' => $pilgrims,
                'payments' => $payments

            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body
            ];

            $getBody = json_decode($body);
            $client = new Client();
            $cache = Cache::remember('umrohCreate:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send, $req, $data, $payments) {
                $result = json_decode($client->post(env('API_PERGI_UMROH') . 'order/', $send)->getBody()->getContents());
                $arrBaru = [
                    'order_id' => $result->data->order_id,
                    'package_id' => $result->data->package_id,
                    'order_code' => $result->data->order_code,
                    'booking_code' => $result->data->booking_code,
                    'pilgrims' => $result->data->pilgrims,
                    'total' => $result->data->total,
                    'isCredit' => $req->payMethod,
                    'paymentId' => $result->data->payments[0]->id
                ];
                $newPayments = [];
                for ($i = 0; $i < count($result->data->payments); $i++) {
                    array_push($newPayments, [
                        'paymentId' => $result->data->payments[$i]->id,
                        'description' => $result->data->payments[$i]->description,
                        'payment_info' => $payments[$i]['type'],
                        'due_date' => $result->data->payments[$i]->due_date,
                        'billed' => $result->data->payments[$i]->billed,
                        'status' => $i + 1,
                        'urlBuktiBayar' => '',
                        'paymentDate' => ''
                    ]);
                }

                $data->methodPayment = $req->payMethod;
                $data->listPayment = $newPayments;
                $data->orderId = $result->data->order_id;
                $data->orderCode = $result->data->order_code;
                $data->status = 1;
                $data->isCancel = 0;

                return $arrBaru;
            });

            if ($cache && $data->update()) {

                $position       = 2;
                $description    = 'Upload Bukti Bayar';
                $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);


                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function umrohDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohDetail:' . date('Y-m-d') . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $body = json_encode([
                'booking_code' => $id
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $client = new Client();
            $cache = Cache::remember('umrohDetail:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->post(env('API_PERGI_UMROH') . 'orders', $send)->getBody()->getContents());
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache->data]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function umrohPay(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohPay:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $body = json_encode([
                'payment_id' => $req->paymentId
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $client = new Client();
            $cache = Cache::remember('umrohPay:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->post(env('API_PERGI_UMROH') . 'payment', $send)->getBody()->getContents());
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache->data]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function umrohStock(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohStock:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $body = json_encode([
                'package_id' => $req->package_id
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $client = new Client();
            $cache = Cache::remember('umrohStock:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->post(env('API_PERGI_UMROH') . 'checkavailability', $send)->getBody()->getContents());
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache->data]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function umrohRelease(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohRelease:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $notif = new NotificationController;

            $body = json_encode([
                'booking_code' => $req->booking_code
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $client = new Client();
            $cache = Cache::remember('umrohRelease:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send, $req) {
                return json_decode($client->post(env('API_PERGI_UMROH') . 'releasestock', $send)->getBody()->getContents());
            });

            $data = UmrohOrder::where('bookingCode', $req->booking_code)->first();
            $data->isCancel = 1;
            $data->paidOffDate = date("Y-m-d h:i:sa");

            if ($cache && $data->update()) {

                $position       = 5;
                $description    = 'Pesanan Dibatalkan';
                $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);

                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = $data->totalPrice;
                $log->paymentStatus = 1;
                $log->description   = 'Pesanan Dibatalkan';

                $log->flag          = 1;

                if ($log->save()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses', 'data' => $cache, 'logTransaction' => $log));
                } else {
                    $response = json_encode(array('statusCode' => '856', 'message' => 'Error save log transaksi'));
                }
            } else {
                $response = json_encode(array('message' => 'Kosong', 'data' => null));
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Canceled', 'Cancel - ' . $data->bookingCode, json_decode($response)->message));
        return $response;
    }

    public function generate()
    {
        try {
            $length_abj = 2;
            $length_ang = 4;

            $huruf = "ABCDEFGHJKMNPRSTUVWXYZ";

            $i = 1;
            $txt_abjad = "";
            while ($i <= $length_abj) {
                $txt_abjad .= $huruf{
                    mt_rand(0, strlen($huruf) - 1)};
                $i++;
            }

            $datejam = date("His");
            $time_md5 = rand(time(), $datejam);
            $cut = substr($time_md5, 0, $length_ang);

            $acak = str_shuffle($txt_abjad . $cut);

            $response = $acak;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function insertRoom(Request $req)
    {
        try {
            $this->validate($req, [
                'packageId'     => 'required',
                'idUserMobile'  => 'required',
                'room'          => 'required',
            ]);

            $notif = new NotificationController;
            $code = $this->generate();
            $bookingCode = 'SPS' . $code;

            $data = new UmrohOrder;

            $data->packageId        = $req->packageId;
            $data->bookingCode      = $bookingCode;
            $data->idUserMobile     = $req->idUserMobile;
            $data->room             = $req->room;
            $data->totalPilgrims    = $req->totalPilgrims;
            $data->totalPrice       = $req->totalPrice;
            $data->departureDate    = $req->departureDate;
            $data->flag             = 1;

            if ($data->save()) {

                $position       = 1;
                $description    = 'Data Jamaah Belum Lengkap';
                $notif->insertUmroh($data->_id, $req->idUserMobile, $description, $position);

                if (isset($this->softBook($req->packageId, $bookingCode, $req->totalPilgrims)['code']) && $this->softBook($req->packageId, $bookingCode, $req->totalPilgrims)['code'] == 200) {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '212', 'message' => 'Gagal Booking']);
                }
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save data Umroh']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateUmroh(Request $req, $id)
    {
        try {

            $notif = new NotificationController;

            $umroh = UmrohOrder::where('_id', $id)->first();

            $data       = $req->data;
            $pilgrims   = [];
            for ($i = 0; $i < count($data); $i++) {
                array_push($pilgrims, [
                    'no'                => $i + 1,
                    'nama'              => $data[$i]['nama'],
                    'tglLahir'          => $data[$i]['tglLahir'],
                    'urlKtp'            => $data[$i]['urlKtp'],
                    'urlKK'             => $data[$i]['urlKK'],
                    'urlBukuNikah'      => $data[$i]['urlBukuNikah'],
                    'urlBukuMiningitis' => $data[$i]['urlBukuMiningitis']
                ]);
            }

            $umroh->listPilgrims         = $pilgrims;

            if ($umroh->update()) {
                if ($umroh->listPayment != null) {
                    $position       = 4;
                    $description    = 'Pembayaran Lunas';
                    $notif->insertUmroh($umroh->_id, $umroh->idUserMobile, $description, $position);
                } else {
                    $position       = 2;
                    $description    = 'Belum Memilih Pembayaran';
                    $notif->insertUmroh($umroh->_id, $umroh->idUserMobile, $description, $position);
                }

                $this->cartDelete($umroh->_id, $umroh->idUserMobile);
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $umroh]);
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save data Umroh']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function pembayaranPenuh($id)
    {
        try {
            $data = UmrohOrder::where('_id', $id)->first();
            $tgl = date('Y-m-d H:i:s', strtotime($data->departureDate . ' -32 days'));
            $start_date = new DateTime(date("Y-m-d H:i:s"));
            $end_date = new DateTime($tgl);
            $interval = $start_date->diff($end_date)->days + 1;

            if ($interval < 30) {
                $tgl = date('Y-m-d H:i:s', strtotime(' + 1 days'));
            }

            $result = [
                [
                    'description'   => 'Pembayaran Penuh',
                    'billed'        => $data->totalPrice,
                    'due_date'      => $tgl,
                    'type'          => 'Pembayaran Penuh'
                ]
            ];
            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function pembayaranKredit($id)
    {
        try {
            $data = UmrohOrder::where('_id', $id)->first();
            $tgl = $data != '' ? date('Y-m-d H:i:s', strtotime($data->departureDate . ' -32 days')) : '';
            $start_date = new DateTime(date("Y-m-d H:i:s"));
            $end_date = new DateTime($tgl);
            $interval = $start_date->diff($end_date)->days + 1;
            $dp = $data != '' ? env('MINIMAL_DP') * (int) $data->totalPilgrims : '0';
            $pembagiTgl = $interval / 2;
            $tglCicilanDua = date('Y-m-d H:i:s', strtotime('-' . (int) $pembagiTgl . ' days', strtotime($tgl)));
            $pengurangan = $data != '' ? (int) $data->totalPrice - $dp : '0';
            $pembagianCicilan = $pengurangan / 2;

            $payArr = [
                [
                    'description' => 'Menunggu Pembayaran',
                    'billed' => (int) $dp,
                    'due_date' => date('Y-m-d H:i:s', strtotime(' + 1 days')),
                    'type' => 'Pembayaran Down Payment'
                ],
                [
                    'description' => 'Pembayaran ke-2',
                    'billed' => $pembagianCicilan,
                    'due_date' => $tglCicilanDua,
                    'type' => 'Pembayaran ke-2'
                ],
                [
                    'description' => 'Pembayaran ke-3',
                    'billed' => $pembagianCicilan,
                    'due_date' => $tgl,
                    'type' => 'Pembayaran ke-3'
                ]
            ];
            $response = $payArr;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function listPayment($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohListPayment:' . date('Y-m-d') . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('umrohListPayment:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($id) {
                $data = UmrohOrder::where('_id', $id)->first();
                $tgl = $data != '' ? date('Y-m-d H:i:s', strtotime($data->departureDate . ' -32 days')) : '';
                $start_date = new DateTime(date("Y-m-d H:i:s"));
                $end_date = new DateTime($tgl);
                $interval = $start_date->diff($end_date)->days + 1;
                $bankArr = [
                    [
                        'bankName' => env('BANK_NAME'),
                        'noRek' => env('BANK_REK')
                    ]
                ];
                $payArr = $this->pembayaranKredit($id);
                if ($interval < 30) {
                    $isCredit = false;
                    $payArr = [];
                } else {
                    $isCredit = true;
                }

                $arrBaru = [
                    'codeBooking' => $data != '' ? $data->bookingCode : '',
                    'totalAmount' => $data != '' ? $data->totalPrice : '',
                    'dueDate' => date('Y-m-d H:i:s', strtotime(' + 1 days')),
                    'isCredit' => $isCredit,
                    'listPayment' => $payArr,
                    'listBank' => $bankArr
                ];

                return $arrBaru;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertKtp(Request $req)
    {
        try {
            $ktp = $req->nama . $req->id;

            if ($req->hasFile('urlKtp')) {

                $files_ktp      = $req->file('urlKtp'); // will get all files
                $originalFile   = 'KTP_' . $ktp . substr($files_ktp->getClientOriginalName(), -4); //Get file original name
                $filePathKtp    = '/data_umroh/ktp/' . $originalFile;
                if (Storage::disk('oss')->exists($filePathKtp)) {
                    Storage::disk('oss')->delete($filePathKtp);
                }

                Storage::disk('oss')->put($filePathKtp, file_get_contents($files_ktp));
            }

            $response = json_encode([
                'statusCode'    => '000',
                'message'       => 'Sukses',
                'urlKtp'        => env('OSS_DOMAIN') . $filePathKtp
            ]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function insertKk(Request $req)
    {
        try {
            $kk = $req->nama . $req->id;

            if ($req->hasFile('urlKK')) {

                $files          = $req->file('urlKK'); // will get all files
                $originalFile   = 'KK_' . $kk . substr($files->getClientOriginalName(), -4); //Get file original name
                $filePath       = '/data_umroh/kk/' . $originalFile;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $response = json_encode([
                'statusCode'    => '000',
                'message'       => 'Sukses',
                'urlKK'         => env('OSS_DOMAIN') . $filePath
            ]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function insertBn(Request $req)
    {
        try {
            $bn = $req->nama . $req->id;

            if ($req->hasFile('urlBukuNikah')) {

                $files          = $req->file('urlBukuNikah'); // will get all files
                $originalFile   = 'BN_' . $bn . substr($files->getClientOriginalName(), -4); //Get file original name
                $filePath       = '/data_umroh/buku_nikah/' . $originalFile;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $response = json_encode([
                'statusCode'    => '000',
                'message'       => 'Sukses',
                'urlBukuNikah'  => env('OSS_DOMAIN') . $filePath
            ]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function insertBm(Request $req)
    {
        try {
            $bm = $req->nama . $req->id;

            if ($req->hasFile('urlBukuMiningitis')) {

                $files          = $req->file('urlBukuMiningitis'); // will get all files
                $originalFile   = 'BM_' . $bm . substr($files->getClientOriginalName(), -4); //Get file original name
                $filePath       = '/data_umroh/buku_miningitis/' . $originalFile;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

            $response = json_encode([
                'statusCode'        => '000',
                'message'           => 'Sukses',
                'urlBukuMiningitis' => env('OSS_DOMAIN') . $filePath
            ]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function buktiBayar(Request $req, $id)
    {
        try {
            $notif = new NotificationController;

            $data = UmrohOrder::where('_id', $id)->first();

            if ($req->hasFile('urlBuktiBayar')) {

                $files          = $req->file('urlBuktiBayar'); // will get all files
                $originalFile   = 'BB_' . time() . $files->getClientOriginalName(); //Get file original name
                $filePath       = '/data_umroh/bukti_bayar/' . $originalFile;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }

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
                        'description'   => 'Menunggu Verifikasi',
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => 1,
                        'urlBuktiBayar' => env('OSS_DOMAIN') . $filePath,
                        'paymentDate'   => $data->listPayment[$i]['paymentDate']
                    ]);
                } elseif ($data->listPayment[$i]['urlBuktiBayar'] != '') {
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
                } else {
                    array_push($arr, [
                        'paymentId'     => $data->listPayment[$i]['paymentId'],
                        'description'   => $data->listPayment[$i]['description'],
                        'payment_info'  => $data->listPayment[$i]['payment_info'],
                        'due_date'      => $data->listPayment[$i]['due_date'],
                        'billed'        => $data->listPayment[$i]['billed'],
                        'status'        => $data->listPayment[$i]['status'],
                        'urlBuktiBayar' => '',
                        'paymentDate'   => $data->listPayment[$i]['paymentDate']
                    ]);
                }
            }

            $data->listPayment  = $arr;
            $data->status       = 1;

            if ($data->update()) {

                $position       = 3;
                $description    = 'Menunggu Verifikasi';
                $notif->insertUmroh($data->_id, $data->idUserMobile, $description, $position);

                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = $price;
                $log->paymentId     = $req->paymentId;
                $log->paymentStatus = 1;
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
                    $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data, 'logTransaction' => $log]);
                } else {
                    $response = response()->json(['statusCode' => '856', 'message' => 'Error save log transaksi']);
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

    public function searchCity(Request $req)
    {
        try {
            $data = UmrohPackage::when($req->departure_from, function ($data) use ($req) {
                $data->where('departure_from', 'LIKE', '%' . $req->departure_from . '%');
            })->orderBy('created_at', 'DESC')->get();
            $response = response()->json(['status' => 'success', 'data' => $data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function searchPrice(Request $req)
    {
        try {
            if ($req->q == 1) {
                $data1 = UmrohPackage::whereBetween('original_price', ['0', '20000000'])->get();
                return response()->json(['status' => 'success', 'data' => $data1]);
            } else if ($req->q == 2) {
                $data1 = UmrohPackage::whereBetween('original_price', ['20000001', '35000000'])->get();
                return response()->json(['status' => 'success', 'data' => $data1]);
            } else if ($req->q == 3) {
                $data1 = UmrohPackage::whereBetween('original_price', ['35000001', '50000000'])->get();
                return response()->json(['status' => 'success', 'data' => $data1]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function searchDate(Request $req)
    {
        try {
            $data = UmrohPackage::when($req->q, function ($data) use ($req) {
                $data->where('departure_date', 'LIKE', '%' . $req->q . '%');
            })->orderBy('created_at', 'DESC')->get();
            $response = response()->json(['status' => 'success', 'data' => $data]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function checkStatus($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohStatus:' . date('Y-m-d') . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('umrohStatus:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($id) {
                $arrBaru = '';
                $data = UmrohOrder::where('_id', $id)->first();
                if ($data) {
                    $count = count($data->listPayment);
                    if ($count > 1) {
                        if ($data->listPayment[0]['status'] != 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[0]['status'],
                                'paymentId'     => $data->listPayment[0]['paymentId'],
                                'description'   => $data->listPayment[0]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        } else if ($data->listPayment[1]['status'] != 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[1]['status'],
                                'paymentId'     => $data->listPayment[1]['paymentId'],
                                'description'   => $data->listPayment[1]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        } else if ($data->listPayment[2]['status'] != 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[2]['status'],
                                'paymentId'     => $data->listPayment[2]['paymentId'],
                                'description'   => $data->listPayment[2]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        } else if ($data->listPayment[0]['status'] == 0 && $data->listPayment[1]['status'] == 0 && $data->listPayment[2]['status'] == 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[0]['status'],
                                'paymentId'     => null,
                                'description'   => $data->listPayment[0]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        }
                    } else {
                        if ($data->listPayment[0]['status'] != 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[0]['status'],
                                'paymentId'     => $data->listPayment[0]['paymentId'],
                                'description'   => $data->listPayment[0]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        } else if ($data->listPayment[0]['status'] == 0) {
                            $arrBaru = [
                                'codeBooking'   => $data->bookingCode,
                                'totalAmount'   => $data->totalPrice,
                                'isCredit'      => $data->methodPayment,
                                'status'        => $data->listPayment[0]['status'],
                                'paymentId'     => null,
                                'description'   => $data->listPayment[0]['description'],
                                'listPayment'   => $data->listPayment,
                                'listPilgrims'  => $data->listPilgrims
                            ];
                        }
                    }
                }

                return $arrBaru;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function softBook($packageId, $code, $pilgrims)
    {
        try {
            $key = Str::of(Cache::get('key', 'umrohStock:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $body = json_encode([
                'booking_code' => $code,
                'package_id' => $packageId,
                'pilgrims' => $pilgrims
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            // return $send;
            $client = new Client();
            $cache = json_decode($client->post(env('API_PERGI_UMROH') . 'softbook', $send)->getBody()->getContents(), true);

            return $cache;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function cetakTiket($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'ticket:' . date('Y-m-d') . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('ticket:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($id) {
                $data = UmrohOrder::where('_id', $id)->first();
                $package = UmrohPackage::where('id', $data->packageId)->first();
                $folder = '/qr_code/' . $data->bookingCode;
                Storage::disk('oss')->makeDirectory($folder);
                for ($i = 0; $i < count($data->listPilgrims); $i++) {
                    $code = QrCode::format('png')->size(250)->errorCorrection('H')
                        ->generate($data->bookingCode . '_' . $data->listPilgrims[$i]['nama'] . '_' . $data->listPilgrims[$i]['tglLahir']);
                    $im = new \Imagick();
                    $im->readImageBlob($code);
                    $im->setImageFormat("png24");
                    $img = base64_encode($im);
                    $file_name = $data->bookingCode . '_' . $data->listPilgrims[$i]['nama'] . '.png';
                    $filePath = $folder . '/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, base64_decode($img));
                    // $im->writeImage("public/".$data->listPilgrims[$i]['nama']."file.png");
                    $tagihan = $data->totalPrice / $data->totalPilgrims;
                    $arrBaru[] = [
                        'title'             => $package->name,
                        'codeBooking'       => $data->bookingCode,
                        'departureDate'     => $data->departureDate,
                        'room'              => $data->room,
                        'totalTagihan'      => $tagihan,
                        'qrCode'            => env('OSS_DOMAIN') . $filePath,
                        'nameJamaah'        => $data->listPilgrims[$i]['nama'],
                        'dateOfBirth'       => date("d-m-Y", strtotime($data->listPilgrims[$i]['tglLahir'])),
                        'noPorsiUmroh'      => null,
                        'termAndCondition'  => 'ini terms'
                    ];
                }

                return $arrBaru;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function package(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'package:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $token = UmrohToken::latest()->first();

            if ($token) {
                $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
                event(new UmrohTokenEvent($tokenTime));
            } else {
                $this->authenticationLogin();
            }

            $post = UmrohPackage::select('created_at')->latest()->first();

            if ($post) {
                $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
                event(new PergiUmrohEvent($time));
            } else {
                $this->packageInsert();
            }

            $data = UmrohPackage::when($req, function ($data) use ($req) {
                if ($req->departure_from != '') {
                    $data->where('departure_from', 'LIKE', '%' . $req->departure_from . '%');
                }
                if ($req->departure_date != '') {
                    $data->where('departure_date', 'like', '%' . $req->departure_date . '%');
                }
                if ($req->sort != '') {
                    $data->orderBy('original_price', $req->sort);
                }
            })->limit(10)->get();

            $dep = '';
            if ($req->departure_from != '') {
                $dep = UmrohPackage::select('departure_from')->where('departure_from', $req->departure_from)->groupBy('departure_from')->get();
            } else {
                $dep = UmrohPackage::select('departure_from')->groupBy('departure_from')->get();
            }

            if ($req->departure_date != '') {
                $depDate = UmrohPackage::select('departure_date')->where('departure_date', 'like', '%' . $req->departure_date . '%')->groupBy('departure_date')->orderBy('departure_date', 'DESC')->get();
            } else {
                $depDate = UmrohPackage::select('departure_date')->groupBy('departure_date')->orderBy('departure_date', 'DESC')->get();
            }

            $arr_dep = [];
            $exist_date = "";
            foreach ($depDate as $val) {
                list($ex1, $ex2) = explode(' ', $val->departure_date);
                $dep_date = substr($ex1, 0, 7);
                if ($dep_date != $exist_date) {
                    array_push($arr_dep, [
                        'departure_date' => $dep_date . '-01'
                    ]);
                }
                $exist_date = $dep_date;
            }
            $arr = [];

            foreach ($data as $val) {
                if (((int)$val->original_price < 20000000) && ($req->price == '1')) {
                    if ($val->discount != '') {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => $val->discount
                        ]);
                    } else {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => null
                        ]);
                    }
                } else if (((int)$val->original_price >= 20000000) && ((int)$val->original_price < 25000000) && ($req->price == '2')) {
                    if ($val->discount != '') {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => $val->discount
                        ]);
                    } else {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => null
                        ]);
                    }
                } else if (((int)$val->original_price >= 25000000) && ((int)$val->original_price < 30000000) && ($req->price == '3')) {
                    array_push($arr, [
                        '_id' => $val->id,
                        'created_at' => null,
                        'updated_at' => null,
                        'titleUmroh' => $val->name,
                        'descUmroh' => $val->description,
                        'nameTravel' => $val->travel_name,
                        'imageUmroh' => $val->image,
                        'departureDate' => $val->departure_date,
                        'departureFrom' => $val->departure_from,
                        'priceUmroh' => $val->original_price,
                        'stockUmroh' => $val->stock
                    ]);
                } else if (((int)$val->original_price > 30000000) && ($req->price == '4')) {
                    if ($val->discount != '') {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => $val->discount
                        ]);
                    } else {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => null
                        ]);
                    }
                } else if ($req->price == '0') {
                    if ($val->discount != '') {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => $val->discount
                        ]);
                    } else {
                        array_push($arr, [
                            '_id' => $val->id,
                            'created_at' => null,
                            'updated_at' => null,
                            'titleUmroh' => $val->name,
                            'descUmroh' => $val->description,
                            'nameTravel' => $val->travel_name,
                            'imageUmroh' => $val->image,
                            'departureDate' => $val->departure_date,
                            'departureFrom' => $val->departure_from,
                            'priceUmroh' => $val->original_price,
                            'stockUmroh' => $val->stock,
                            'discount' => null
                        ]);
                    }
                }
            }

            if ($arr) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'dataDeparture' => $dep, 'departureDate' => $arr_dep, 'data' => $arr]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function promosion()
    {
        try {
            $client = new Client();

            $token = UmrohToken::latest()->first();

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ];

            $data = $client->get(env('API_PERGI_UMROH') . 'promotions', $send)->getBody()->getContents();

            // $length = json_decode($data);

            $response = $data;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function promosionById($id)
    {
        try {
            $client = new Client();

            $token = UmrohToken::latest()->first();

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ];

            $data = $client->get(env('API_PERGI_UMROH') . 'promotion/' . $id, $send)->getBody()->getContents();

            // $length = json_decode($data);

            $response = $data;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function promosionPackages($id)
    {
        try {
            $client = new Client();

            $token = UmrohToken::latest()->first();

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];

            $data = $client->get(env('API_PERGI_UMROH') . 'promotion/' . $id . '/packages', $send)->getBody()->getContents();

            // $length = json_decode($data);

            $response = $data;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function transactionHistory(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'transactionHist:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('transactionHist:' . date('Y-m-d') . $req->idUserMobile, env('CACHE_DURATION'), function () use ($req) {
                $data = UmrohOrder::where('idUserMobile', $req->idUserMobile)->orderBy('updated_at', 'DESC')->get();

                $arr        = [];
                foreach ($data as $val) {
                    $package = UmrohPackage::where('id', $val->packageId)->first();
                    if (!empty($val->listPilgrims) && !empty($val->listPayment)) {
                        if ($val->isCancel == 1) {
                            array_push($arr, [
                                '_id'           => $val->_id,
                                'codeBooking'   => $val->bookingCode,
                                'position'      => 5,
                                'name'          => $package->name,
                                'type'          => 'Order Cencel',
                                'date'          => date("Y-m-d h:i:s", strtotime($val->paidOffDate)),
                                'totalPrice'    => $val->totalPrice
                            ]);
                        } else if ($val->status == 0) {

                            for ($i = 0; $i < count($val->listPilgrims); $i++) {
                                if (empty($val->listPilgrims[$i]['urlKtp'])) {
                                    $ret = 0;
                                } else if (empty($val->listPilgrims[$i]['urlKK'])) {
                                    $ret = 0;
                                } else if (empty($val->listPilgrims[$i]['urlBukuNikah'])) {
                                    $ret = 0;
                                } else if (empty($val->listPilgrims[$i]['urlBukuMiningitis'])) {
                                    $ret = 0;
                                } else {
                                    $ret = 1;
                                }
                            }
                            if ($ret == 1) {
                                array_push($arr, [
                                    '_id'           => $val->_id,
                                    'codeBooking'   => $val->bookingCode,
                                    'position'      => 4,
                                    'name'          => $package->name,
                                    'type'          => 'Ticket Umroh',
                                    'date'          => date("Y-m-d h:i:s", strtotime($val->paidOffDate)),
                                    'totalPrice'    => $val->totalPrice
                                ]);
                            }
                        }
                    }
                }
                return $arr;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '333', 'message' => 'Data History NULL']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function transactionHistDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'transactionHistId:' . date('Y-m-d') . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('transactionHistId:' . date('Y-m-d') . $id, env('CACHE_DURATION'), function () use ($id) {
                $data = UmrohOrder::where('_id', $id)->first();
                if ($data->methodPayment == null) {
                    $arr =  [
                        '_id' => $data->_id,
                        'codeBooking' => $data->bookingCode,
                        'packageId' => $data->packageId,
                        'totalJamaah' => $data->totalPilgrims,
                        'room' => $data->room,
                        'departureDate' => $data->departureDate,
                        'totalPembayaran' => $data->totalPrice,
                        'listPilgrims' => $data->listPilgrims,
                        'payMethod' => $data->methodPayment,
                        'paymentId' => ''
                    ];
                } else {
                    $arr =  [
                        '_id' => $data->_id,
                        'codeBooking' => $data->bookingCode,
                        'packageId' => $data->packageId,
                        'totalJamaah' => $data->totalPilgrims,
                        'room' => $data->room,
                        'departureDate' => $data->departureDate,
                        'totalPembayaran' => $data->totalPrice,
                        'listPilgrims' => $data->listPilgrims,
                        'payMethod' => $data->methodPayment,
                        'paymentId' => $data->listPayment[0]['paymentId']
                    ];
                }
                return $arr;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function syaratKetentuan()
    {
        try {
            $key = Str::of(Cache::get('key', 'syaratKententuan:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('syaratKententuan:' . date('Y-m-d'), env('CACHE_DURATION'), function () {
                $arr =  [
                    'sk_url' => env('SYARAT_KETENTUAN')
                ];

                return $arr;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function dueDate($date)
    {
        $data = UmrohOrder::where('status', '!=', 0)->orWhere('isCancel', '!=', 1)->get();
        $arr_list = [];
        foreach ($data as $key => $val) {
            array_push($arr_list, ['id' => $val->_id]);
            if ($val->listPayment != null) {
                if ($arr_list[$key]['id'] == $val->_id) {
                    if ($val->listPayment) {
                        for ($i = 0; $i < count($val->listPayment); $i++) {
                            $arr_list2[$arr_list[$key]['id']][] = [
                                'paymentId'     => $val->listPayment[$i]['paymentId'],
                                'description'   => $val->listPayment[$i]['description'],
                                'payment_info'  => isset($val->listPayment[$i]['payment_info']) ? $val->listPayment[$i]['payment_info'] : '',
                                'due_date'      => $val->listPayment[$i]['due_date'],
                                'billed'        => $val->listPayment[$i]['billed'],
                                'status'        => $val->listPayment[$i]['status'],
                                'urlBuktiBayar' => $val->listPayment[$i]['urlBuktiBayar'],
                                'paymentDate'   => isset($val->listPayment[$i]['paymentDate']) ? $val->listPayment[$i]['paymentDate'] : ''
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($arr_list2)) {
            foreach ($arr_list2 as $key => $val) {
                foreach ($val as $index => $pay) {
                    $due = date("Y-m-d", strtotime($pay['due_date']));
                    if ($due < $date && ($pay['status'] == 2 || $pay['status'] == 3)) {
                        $arr_list2[$key][$index]['description'] = 'Jatuh Tempo';
                        $arr_list2[$key][$index]['status'] = 4;

                        UmrohOrder::where('_id', $key)->update(['listPayment' => $arr_list2[$key]]);
                    }
                }
            }
            return $arr_list2;
        }
    }

    public function updateStatus($id)
    {
        try {
            $data = UmrohOrder::where('_id', $id)->first();
            $count = count($data->listPayment);
            $payDate = 0;
            if ($count > 1) {
                if ($data->listPayment[0]['status'] != 0) {
                    $status = $data->listPayment[0]['status'];
                } else if ($data->listPayment[1]['status'] != 0) {
                    $status = $data->listPayment[1]['status'];
                } else if ($data->listPayment[2]['status'] != 0) {
                    $status = $data->listPayment[2]['status'];
                } else if ($data->listPayment[0]['status'] == 0 && $data->listPayment[1]['status'] == 0 && $data->listPayment[2]['status'] == 0) {
                    $status = $data->listPayment[0]['status'];
                    $payDate = 1;
                }
            } else {
                if ($data->listPayment[0]['status'] != 0) {
                    $status = $data->listPayment[0]['status'];
                } else if ($data->listPayment[0]['status'] == 0) {
                    $status = $data->listPayment[0]['status'];
                    $payDate = 1;
                }
            }

            $data->status = $status;
            if ($payDate != 0) {
                $data->paidOffDate = date("Y-m-d h:i:sa");
            }

            if ($data->update()) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            } else {
                $response = response()->json(['statusCode' => '856', 'message' => 'Error save log transaksi']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function addCarts(Request $req)
    {
        try {
            $this->validate($req, [
                'packageId'     => 'required',
                'idUserMobile'  => 'required',
                'room'          => 'required',
            ]);

            $order = json_decode($this->insertRoomCart($req));

            $data = new HijrahCarts;

            $data->packageId        = $order->data->_id;
            $data->bookingCode      = $order->data->bookingCode;
            $data->idUserMobile     = $req->idUserMobile;
            $data->room             = $req->room;
            $data->totalPilgrims    = $req->totalPilgrims;
            $data->totalPrice       = $req->totalPrice;
            $data->departureDate    = $req->departureDate;
            $data->flag             = 1;

            if ($data->save()) {
                return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save data Umroh']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function insertRoomCart(Request $req)
    {
        try {

            $code = $this->generate();
            $bookingCode = 'SPS' . $code;

            $data = new UmrohOrder;

            $data->packageId        = $req->packageId;
            $data->bookingCode      = $bookingCode;
            $data->idUserMobile     = $req->idUserMobile;
            $data->room             = $req->room;
            $data->totalPilgrims    = $req->totalPilgrims;
            $data->totalPrice       = $req->totalPrice;
            $data->departureDate    = $req->departureDate;
            $data->flag             = 1;

            if ($data->save()) {

                if (isset($this->softBook($req->packageId, $bookingCode, $req->totalPilgrims)['code']) && $this->softBook($req->packageId, $bookingCode, $req->totalPilgrims)['code'] == 200) {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return json_encode(['statusCode' => '212', 'message' => 'Gagal Booking']);
                }
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save data Umroh']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function orderCart($id)
    {
        try {

            $data = new UmrohOrder;
            $value = HijrahCarts::where('_id', $id)->first();

            $data->packageId        = $value->packageId;
            $data->bookingCode      = $value->bookingCode;
            $data->idUserMobile     = $value->idUserMobile;
            $data->room             = $value->room;
            $data->totalPilgrims    = $value->totalPilgrims;
            $data->totalPrice       = $value->totalPrice;
            $data->departureDate    = $value->departureDate;
            $data->flag             = $value->flag;

            if ($data->save()) {
                if ($value->delete()) {
                    return json_encode(['statusCode' => '000', 'message' => 'Sukses']);
                }
            } else {
                return json_encode(['statusCode' => '999', 'message' => 'Error save data Umroh']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function destroyCarts(Request $req, $id)
    {
        try {
            $count = HijrahCarts::where('packageId', $id)->where('idUserMobile', $req->idUserMobile)->delete();
            if ($count > 0) {
                $umroh = UmrohOrder::where('_id', $id)->first();
                $this->relese($umroh->bookingCode);
                $umroh->delete();
                Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Carts"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function destroyEventCarts($id)
    {
        try {
            $data = HijrahCarts::where('_id', $id)->first();
            $umroh = UmrohOrder::where('_id', $data->packageId)->first();
            if ($data->delete()) {
                $this->relese($umroh->bookingCode);
                $umroh->delete();
                Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Carts"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getCart(Request $req)
    {
        try {
            $data = HijrahCarts::where('idUserMobile', $req->idUserMobile)->get();
            $arr = [];
            foreach ($data as $value) {
                $order = UmrohOrder::where('_id', $value->packageId)->first();
                $package = UmrohPackage::where('id', $order->packageId)->first();
                array_push($arr, [
                    '_id' => $value->packageId,
                    'codeBooking' => $value->bookingCode,
                    'idUserMobile' => $value->idUserMobile,
                    'packageId' => $order->packageId,
                    'name' => $package->name,
                    'room' => $value->room,
                    'totalPilgrims' => $value->totalPilgrims,
                    'totalPrice' => $value->totalPrice,
                ]);
            }
            return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $arr));
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function notifikasi(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'notifikasi:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($key));
            event(new NotificationEvent($key));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('notifikasi:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                $data = UmrohOrder::where('idUserMobile', $req->idUserMobile)->orderBy('updated_at', 'DESC')->get();

                $arr = [];
                $desc = '';
                foreach ($data as $value) {
                    $package = UmrohPackage::where('id', $value->packageId)->first();
                    if ($value->isCancel == 1) {
                        $position = 5;
                        $desc = 'Order telah dibatalkan';
                    } else if (empty($value->listPilgrims)) {
                        $position = 1;
                        $desc = 'Mohon untuk melengkapi data diri';
                    } else if (empty($value->methodPayment)) {
                        $position = 2;
                        $desc = 'Mohon untuk memilih metode pembayaran';
                    } else if (empty($value->listPayment[0]['urlBuktiBayar'])) {
                        $position = 2;
                        $desc = 'Mohon lakukan upload bukti bayar';
                    } else {
                        $ret = 0;
                        for ($i = 0; $i < count($value->listPilgrims); $i++) {
                            if (empty($value->listPilgrims[$i]['urlKtp'])) {
                                $ret = 0;
                            } else if (empty($value->listPilgrims[$i]['urlKK'])) {
                                $ret = 0;
                            } else if (empty($value->listPilgrims[$i]['urlBukuNikah'])) {
                                $ret = 0;
                            } else if (empty($value->listPilgrims[$i]['urlBukuMiningitis'])) {
                                $ret = 0;
                            } else {
                                $ret = 1;
                            }
                        }
                        for ($i = 0; $i < count($value->listPayment); $i++) {
                            if ($value->listPayment[$i]['status'] == 0) {
                                if ($ret == 1) {
                                    $position = 4;
                                } else {
                                    $position = 3;
                                    $desc = 'Mohon Lengkapi Dokumen';
                                }
                            } else if ($value->listPayment[0]['status'] == 1) {
                                $position = 3;
                                $desc = $value->listPayment[0]['description'];
                            } else if ($value->listPayment[1]['status'] == 2) {
                                $position = 3;
                                $desc = $value->listPayment[1]['description'] . " " . $value->listPayment[$i]['billed'];
                            } else if ($value->listPayment[2]['status'] == 3) {
                                $position = 3;
                                $desc = $value->listPayment[2]['description'] . " " . $value->listPayment[$i]['billed'];
                            }
                        }
                    }
                    array_push($arr, [
                        '_id' => $value->_id,
                        'codeBooking' => $value->bookingCode,
                        'position' => $position,
                        'type' => 1,
                        'title' => $package->name,
                        'description' => $desc
                    ]);
                }

                return $arr;
            });

            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            } else {
                return response()->json(['message' => 'Kosong', 'data' => null]);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function cartDelete($id, $idUserMobile)
    {
        try {
            $data = HijrahCarts::where('packageId', $id)->where('idUserMobile', $idUserMobile)->count();
            if ($data > 0) {
                if (HijrahCarts::where('packageId', $id)->where('idUserMobile', $idUserMobile)->delete()) {
                    Cache::forget('apiHomeResultAladhan:' . date('Y-m-d'));
                    return json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    return json_encode(array('statusCode' => '334', 'message' => "Gagal Hapus Carts"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function relese($bookingCode)
    {
        try {

            $token = UmrohToken::latest()->first();

            $body = json_encode([
                'booking_code' => $bookingCode
            ]);

            $send = [
                'headers'   => [
                    "Authorization" => $token->token_type . " " . $token->access_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'      => $body,
                // 'timeout' => ENV('GUZZLE_TIMEOUT')
            ];
            $client = new Client();
            $cache = Cache::remember('umrohRelease:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->post(env('API_PERGI_UMROH') . 'releasestock', $send)->getBody()->getContents());
            });

            $data = UmrohOrder::where('bookingCode', $bookingCode)->first();
            $data->isCancel = 1;
            $data->paidOffDate = date("Y-m-d h:i:sa");

            if ($cache && $data->update()) {

                $log = new LogTransaction;

                $log->bookingCode   = $data->bookingCode;
                $log->idUserMobile  = $data->idUserMobile;
                $log->totalPrice    = $data->totalPrice;
                $log->paymentStatus = 1;
                $log->description   = 'Pesanan di Cart Dibatalkan';
                $log->flag          = 1;

                if ($log->save()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => 'Sukses', 'data' => $cache, 'logTransaction' => $log));
                } else {
                    $response = json_encode(array('statusCode' => '856', 'message' => 'Error save log transaksi'));
                }
            } else {
                $response = json_encode(array('message' => 'Kosong', 'data' => null));
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
