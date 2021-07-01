<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MootaMutations;
use App\TransactionTopup;
use App\TransferTransactions;
use App\CashTransactions;
use Illuminate\Support\Str;
use App\Http\Controllers\APIEksternal\OyController;
use App\Http\Controllers\APIEksternal\MobilePulsaController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Storage;
use Illuminate\Support\Facades\Cache;
use App\Events\CacheFlushEvent;
use App\Helpers\FormatDate;
use Carbon\Carbon;
use DateTime;

class MootaController extends Controller
{

    public function callbackWebhookMoota(Request $req)
    {
        
        try {
            
            $oy = new OyController;
            $mp = new MobilePulsaController;
            $getToken = $req->apikey;
            $token = env('TOKEN_MOOTA');
            $responseMootas = json_decode($req->all()[0],true);

            if (env('APP_ENV') != 'production' && $getToken == 'testing'){
                foreach ($responseMootas as $datas) {
                    $moota = new MootaMutations;

                    $moota->mutationId = isset($datas['id']) ? $datas['id'] : $datas['mutation_id'];
                    $moota->bankId = isset($datas['bank_id']) ? $datas['bank_id'] : '';
                    $moota->accountNumber = isset($datas['account_number']) ? $datas['account_number'] : 0;
                    $moota->bankType = isset($datas['bank_type']) ? $datas['bank_type'] : '';
                    $moota->date = isset($datas['date']) ? $datas['date'] : '';
                    $moota->amount = isset($datas['amount']) ? $datas['amount'] : 0;
                    $moota->description = isset($datas['description']) ? $datas['description'] : '';
                    $moota->type = isset($datas['type']) ? $datas['type'] : '';
                    $moota->balance = isset($datas['balance']) ? $datas['balance'] : 0;

                    if ($moota->save()) {
                        $count = TransferTransactions::where('amount', (int) $datas['amount'])->count();
                        if ($count > 0) {
                            $trf = Transfertransactions::where('amount', (int) $datas['amount'])->get();
                            foreach ($trf as $value) {
                                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d')) {
                                    $oy->disbursement($value->_id);
                                }
                            }
                        }else {
                            $trf = TransactionTopup::where('totalTransfer', (int) $datas['amount'])->get();
                            foreach ($trf as $value) {
                                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d')) {
                                    $mp->transactionFromMoota($value->_id);
                                }
                            }
                        }
                    }
                }
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses Simpan Mutasi Moota']);
            } elseif (env('APP_ENV') == 'production' && $getToken == $token){
                foreach ($responseMootas as $datas) {
                    $moota = new MootaMutations;

                    $moota->mutationId = isset($datas['id']) ? $datas['id'] : $datas['mutation_id'];
                    $moota->bankId = isset($datas['bank_id']) ? $datas['bank_id'] : '';
                    $moota->accountNumber = isset($datas['account_number']) ? $datas['account_number'] : 0;
                    $moota->bankType = isset($datas['bank_type']) ? $datas['bank_type'] : '';
                    $moota->date = isset($datas['date']) ? $datas['date'] : '';
                    $moota->amount = isset($datas['amount']) ? $datas['amount'] : 0;
                    $moota->description = isset($datas['description']) ? $datas['description'] : '';
                    $moota->type = isset($datas['type']) ? $datas['type'] : '';
                    $moota->balance = isset($datas['balance']) ? $datas['balance'] : 0;

                    if ($moota->save()) {
                        $count = TransferTransactions::where('amount', (int) $datas['amount'])->count();
                        if ($count > 0) {
                            $trf = Transfertransactions::where('amount', (int) $datas['amount'])->get();
                            foreach ($trf as $value) {
                                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d')) {
                                    $oy->disbursement($value->_id);
                                }
                            }
                        }else {
                            $trf = TransactionTopup::where('totalTransfer', (int) $datas['amount'])->get();
                            foreach ($trf as $value) {
                                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d')) {
                                    $mp->transactionFromMoota($value->_id);
                                }
                            }
                        }
                    }
                }
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses Simpan Mutasi Moota']);
            } else {
                $response = json_encode(['statusCode' => '443', 'message' => 'Gagal Simpan Mutasi Moota']);
            }
            
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function webhookMoota(Request $req)
    {
        
        try {
            
            $payload = json_encode($req->all());
            $signature = hash_hmac('sha256', $payload, ENV('SECRET_WEBHOOK_MOOTA'));
            $getToken = $req->header('Signature');
            $responseMootas = $req->all();
            if ($getToken == $signature) {
                foreach ($responseMootas as $datas) {
                    
                    $moota = new MootaMutations;

                    $moota->mutationId = isset($datas['id']) ? $datas['id'] : $datas['mutation_id'];
                    $moota->bankId = isset($datas['bank_id']) ? $datas['bank_id'] : '';
                    $moota->accountNumber = isset($datas['account_number']) ? $datas['account_number'] : 0;
                    $moota->bankType = isset($datas['bank']['bank_type']) ? $datas['bank']['bank_type'] : '';
                    $moota->date = isset($datas['date']) ? $datas['date'] : '';
                    $moota->amount = isset($datas['amount']) ? $datas['amount'] : 0;
                    $moota->description = isset($datas['description']) ? $datas['description'] : '';
                    $moota->type = isset($datas['type']) ? $datas['type'] : '';
                    $moota->balance = isset($datas['balance']) ? $datas['balance'] : 0;

                    if ($moota->save()) {
                        $this->getDataMootaForMp($moota->amount, $moota->date);
                        $this->getDataMootaForOy($moota->amount, $moota->date);
                        $this->getDataMootaSetorTarik($moota->amount, $moota->date);
                        // $this->getDataMootaMerchant($moota->amount, $moota->date);
                    }
                }
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses Simpan Mutasi Moota']);
            } else {
                $response = json_encode(['statusCode' => '443', 'message' => 'Gagal Simpan Mutasi Moota']);
            }
            
            
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function profile()
    {
        
        try {
            $key = Str::of(Cache::get('key', 'profileMoota:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('profileMoota:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->get(env('URL_MOOTA')."profile", $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function balance()
    {
        
        try {
            $key = Str::of(Cache::get('key', 'balanceMoota:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('balanceMoota:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->get(env('URL_MOOTA')."balance", $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function bankAccount()
    {
        
        try {
            $key = Str::of(Cache::get('key', 'bankAccountMoota:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('bankAccountMoota:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->get(env('URL_MOOTA')."bank", $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function bankDetail()
    {
        
        try {
            $id = ENV('ID_MOOTA_USER');
            $key = Str::of(Cache::get('key', 'bankAccountMoota:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('bankAccountMoota:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($client, $send, $id) {
                return json_decode($client->get(env('URL_MOOTA')."bank/".$id, $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function mutation()
    {
        
        try {
            $key = Str::of(Cache::get('key', 'mutationMoota:' . date('Y-m-d') . ':' . ENV('ID_MOOTA_USER')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('mutationMoota:' . date('Y-m-d') . ':' . ENV('ID_MOOTA_USER'), env('CACHE_DURATION'), function () use ($client, $send) {
                return json_decode($client->get(env('URL_MOOTA')."bank/".ENV('ID_MOOTA_USER')."/mutation", $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }  
            
    public function lastMutation()
    {
        
        try {
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];

            $data = json_decode($client->get(env('URL_MOOTA')."bank/".ENV('ID_MOOTA_USER')."/mutation/recent/10", $send)->getBody()->getContents());
            
            if ($data) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function searchByAmount(Request $req)
    {
        
        try {
            $oy = new OyController;
            $key = Str::of(Cache::get('key', 'searchAmount:' . date('Y-m-d') . ':' . $req->amount))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('searchAmount:' . date('Y-m-d') . ':' . $req->amount, env('CACHE_DURATION'), function () use ($client, $send, $req) {
                return json_decode($client->get(env('URL_MOOTA')."bank/".ENV('ID_MOOTA_USER')."/mutation/search/".$req->amount, $send)->getBody()->getContents());
            });
            
            if ($cache) {
                if ($cache->mutation != null) {
                    foreach($cache->mutation as $val){
                        if (date('Y-m-d', strtotime($val->date)) == date('Y-m-d')) {
                            $value = TransferTransactions::where('amount', $val->amount)->first();
                            if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d') && $value->trxId == null) {
                                $response = $oy->disbursement($value->_id);
                            }else {
                                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $value]);
                            }
                        }
                    }
                }else {
                    $response = json_encode(['statusCode' => '549', 'message' => 'Transfer sedang dalam proses, Mohon Tunggu.']);
                }
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function searchByDesc(Request $req)
    {
        
        try {
            $key = Str::of(Cache::get('key', 'searchDesc:' . date('Y-m-d') . ':' . $req->desc))->explode(':')[1];
            event(new CacheFlushEvent($key));
            $client = new Client();

            $headers = [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '. ENV('TOKEN_MOOTA'),
                ];

            $send = [
                'headers'   => $headers
            ];
            $cache = Cache::remember('searchDesc:' . date('Y-m-d') . ':' . $req->desc, env('CACHE_DURATION'), function () use ($client, $send, $req) {
                return json_decode($client->get(env('URL_MOOTA')."bank/".ENV('ID_MOOTA_USER')."/mutation/search/description/".$req->desc, $send)->getBody()->getContents());
            });
            
            if ($cache) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
            }else {
                $response = json_encode(['statusCode' => '404', 'message' => 'Kosong', 'data' => null]);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataMootaForMp($amount, $date)
    {
        
        try {
            $mp = new MobilePulsaController;
            $count = TransactionTopup::where('totalTransfer', (int) $amount)->count();
            if ($count > 0) {
                $value = TransactionTopup::where('totalTransfer',  (int) $amount)->first();
                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d', strtotime($date))) {
                    $response = $mp->transactionFromMoota($value->_id);
                }else {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $value]);
                }
            }else {
                $response = json_encode(['statusCode' => '902', 'message' => 'Bukan di Mobile Pulsa']);
            }
    } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }   

    public function getDataMootaForOy($amount, $date)
    {
        
        try {
            $oy = new OyController;
            $count = TransferTransactions::where('amount', $amount)->count();
            if ($count > 0) {
                $value = TransferTransactions::where('amount', $amount)->first();
                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d', strtotime($date)) && $value->trxId == "") {
                    $response = $oy->disbursement($value->_id);
                }else {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $value]);
                }
            }else {
                $response = json_encode(['statusCode' => '902', 'message' => 'Bukan di OY']);
            }
    } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataMootaSetorTarik($amount, $date)
    {
        
        try {
            $oy = new OyController;
            $count = CashTransactions::where('totalTransaksi',  (int) $amount)->where('transactionType', 'CASH_OUT')->count();
            if ($count > 0) {
                $value = CashTransactions::where('totalTransaksi',  (int) $amount)->where('transactionType', 'CASH_OUT')->first();
                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d', strtotime($date))) {
                    $response = $oy->inOutMoney($value->_id);
                }else {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $value]);
                }
            }else {
                $response = json_encode(['statusCode' => '902', 'message' => 'Bukan di Setor Tarik Tunai']);
            }
    } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }  

    public function getDataMootaMerchant($amount, $date)
    {
        
        try {
            $oy = new OyController;
            $count = MerchantTransaction::where('totalTransfer',  (int) $amount)->count();
            if ($count > 0) {
                $value =  MerchantTransaction::where('totalTransfer',  (int) $amount)->first();
                if (date('Y-m-d', strtotime($value->created_at)) == date('Y-m-d', strtotime($date))) {
                    $response = $oy->mootaDisbursementMerchant($value->_id);
                }else {
                    $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $value]);
                }
            }else {
                $response = json_encode(['statusCode' => '902', 'message' => 'Bukan di Hijrah Merchant']);
            }
    } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }   

    public function sendMoota($id){
        $data = MootaMutations::where('_id', $id)->first();
        if ($data) {
            return $this->getDataMootaForMp($data->amount, $data->date);
            $this->getDataMootaForOy($data->amount, $data->date);
        }
    }
}
