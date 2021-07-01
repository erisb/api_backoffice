<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Events\CacheFlushEvent;
use App\SholatAlarms;

class JadwalSholatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['kemenagKab','kemenagSholat','aladhanSholat','aladhanSholatByCity','getKodeKotaByNama','aladhanBydate','searchSholatFatimah']]);
    }

    public function kemenagProv()
    {
        try {
            $key = Str::of(Cache::get('key','apiKemenagProv:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $params = [
                'param_token' => env('TOKEN_KEMENAG'),
            ];
            $result = Cache::remember('apiKemenagProv:'.date('Y-m-d'), env('CACHE_DURATION'), function () use ($client, $params) {
                return json_decode($client->get(
                    env('API_KEMENAG') . 'getShalatProv',
                    ['query' => $params]
                )->getBody()->getContents());
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function kemenagKab(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiKemenagKab:'.date('Y-m-d').':'.$req->param_prov))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $params = [
                'param_token' => env('TOKEN_KEMENAG'),
                'param_prov' => $req->param_prov
            ];
            $result = Cache::remember('apiKemenagKab:'.date('Y-m-d').':'.$req->param_prov, env('CACHE_DURATION'), function () use ($client, $params) {
                return json_decode($client->get(
                    env('API_KEMENAG') . 'getShalatKabKo',
                    ['query' => $params]
                )->getBody()->getContents());
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function kemenagSholat(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiKemenagSholat:'.date('Y-m-d').':'.$req->param_bln.':'.$req->param_thn.':'.$req->param_prov.':'.$req->param_kabko))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiKemenagSholat:'.date('Y-m-d').':'.$req->param_bln.':'.$req->param_thn.':'.$req->param_prov.':'.$req->param_kabko, env('CACHE_DURATION'), function () use ($client, $req) {
                return $client->post(
                    env('API_KEMENAG') . 'getShalatJadwal',
                    ['form_params' => [
                        'param_token' => env('TOKEN_KEMENAG'),
                        'param_prov' => $req->param_prov,
                        'param_kabko' => $req->param_kabko,
                        'param_thn' => $req->param_thn,
                        'param_bln' => $req->param_bln
                    ]]
                )->getBody()->getContents();
            });
            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function aladhanSholat(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiAladhanSholat:'.date('Y-m-d').':'.$req->month.':'.$req->year.':'.$req->latitude.':'.$req->longitude))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiAladhanSholat:'.date('Y-m-d').':'.$req->month.':'.$req->year.':'.$req->latitude.':'.$req->longitude,env('CACHE_DURATION'),function() use ($client,$req){
                return $client->get(env('API_ALADHAN').'/calendar',
                        ['query' => [
                                            'latitude' => $req->latitude,
                                            'longitude' => $req->longitude,
                                            'method' => env('METHOD_ALADHAN'),
                                            'month' => $req->month,
                                            'year' => $req->year
                                            ]])->getBody()->getContents();
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function aladhanSholatByDate(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiAladhanSholatByDate:'.$req->date.':'.$req->latitude.':'.$req->longitude))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiAladhanSholatByDate:'.$req->date.':'.$req->latitude.':'.$req->longitude,env('CACHE_DURATION'),function() use ($client,$req){
                return $client->get(env('API_ALADHAN').'/timings/'.$req->date,
                        ['query' => [
                                            'latitude' => $req->latitude,
                                            'longitude' => $req->longitude,
                                            'method' => env('METHOD_ALADHAN')
                                            ]])->getBody()->getContents();
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function aladhanSholatByCity(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiAladhanSholatByCity:'.$req->city.':'.$req->country))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiAladhanSholatByCity:'.$req->city.':'.$req->country,env('CACHE_DURATION'),function() use ($client,$req){
                return $client->get(env('API_ALADHAN').'/timingsByCity',
                        ['query' => [
                                            'city' => $req->city,
                                            'country' => $req->country,
                                            'method' => 8
                                            ]])->getBody()->getContents();
            });

            $data = json_decode($result);
            $response = json_encode([
                'statusCode' => '000', 
                'message' => "Sukses",
                'data' => [
                    'tanggal' => [
                        'format' => 'json',
                        'kota'   => null,
                        'tanggal' => $data->data->date->readable
                        ],
                    'jadwal' => [
                        'ashar' => $data->data->timings->Asr,
                        'dhuha' => $data->data->timings->Sunrise,
                        'dzuhur' => $data->data->timings->Dhuhr,
                        'imsak' => $data->data->timings->Imsak,
                        'isya' => $data->data->timings->Isha,
                        'maghrib' => $data->data->timings->Maghrib,
                        'subuh' => $data->data->timings->Fajr,
                        'tanggal' => $data->data->date->readable,
                        'terbit' => $data->data->timings->Sunrise,
                    ],
                ]]);
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function indoSholatByDate(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiFatimah:'.$req->kode_kota.':'.$req->tanggal))->explode(':')[2];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiFatimah:'.$req->kode_kota.':'.$req->tanggal,env('CACHE_DURATION'),function() use ($client,$req){
                return $client->get(env('API_FATIMAH').'/sholat/format/json/jadwal/kota/'.$req->kode_kota.'/tanggal/'.$req->tanggal)->getBody()->getContents();
            });
            
            $decodeData = json_decode($result);
            $response = json_encode([
                'statusCode' => '000', 
                'message' => "Sukses",
                'data' =>[ 'tanggal' => $decodeData->query, 'jadwal' => $decodeData->jadwal->data]
                ]);
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function getKodeKotaByNama(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','apiGetKotaByNama:'.date('Y-m-d').':'.$req->nama_kota))->explode(':')[2];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiGetKotaByNama:'.date('Y-m-d').':'.$req->nama_kota,env('CACHE_DURATION'),function() use ($client,$req){
                return $client->get(env('API_FATIMAH').'/sholat/format/json/kota/nama/'.$req->nama_kota)->getBody()->getContents();
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function getKotaFatimah()
    {
        try {
            $key = Str::of(Cache::get('key','apiGetKota:'.date('Y-m-d')))->explode(':')[2];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiGetKota:'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client){
                return $client->get(env('API_FATIMAH').'/sholat/format/json/kota')->getBody()->getContents();
            });

            $response = $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function indoSholat($kode_kota)
    {
        try {
            $key = Str::of(Cache::get('key','apiFatimah:'.$kode_kota.':'.date('Y-m-d')))->explode(':')[2];
            event(new CacheFlushEvent($key));

            $client = new Client();
            $result = Cache::remember('apiFatimah:'.$kode_kota.':'.date('Y-m-d'),env('CACHE_DURATION'),function() use ($client,$kode_kota){
                return $client->get(env('API_FATIMAH').'/sholat/format/json/jadwal/kota/'.$kode_kota.'/tanggal/'.date('Y-m-d'))->getBody()->getContents();
            });

            return $result;
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function aladhanBydate(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'searchSholatAladhanByDate:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $resultAll = Cache::remember('searchSholatAladhanByDate:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                
                $resultJadwalSholat = $req != null ? json_decode($this->aladhanSholatByDate($req)) : null;
                $dataAlarm = $req != null ? $this->getAlarmSholat($req->imei,$req->tanggal) : null;
                foreach ($dataAlarm as $value){
                    $arrAlarm[] = [
                        'idWaktuSholat' => $value['idWaktuSholat'],
                        'alarmWaktuSholat' => $value['alarmWaktuSholat'],
                    ];
                }

                return json_encode([
                    'statusCode' => '000', 
                    'message' => "Sukses",
                    'data' => [
                        'tanggal' => [
                            'format' => 'json',
                            'kota'   => null,
                            'tanggal' => $resultJadwalSholat->data->date->readable
                            ],
                        'jadwal' => [
                            'ashar' => $resultJadwalSholat->data->timings->Asr,
                            'dhuha' => $resultJadwalSholat->data->timings->Sunrise,
                            'dzuhur' => $resultJadwalSholat->data->timings->Dhuhr,
                            'imsak' => $resultJadwalSholat->data->timings->Imsak,
                            'isya' => $resultJadwalSholat->data->timings->Isha,
                            'maghrib' => $resultJadwalSholat->data->timings->Maghrib,
                            'subuh' => $resultJadwalSholat->data->timings->Fajr,
                            'tanggal' => $resultJadwalSholat->data->date->readable,
                            'terbit' => $resultJadwalSholat->data->timings->Sunrise,
                        ],
                        'listAlarm' => $dataAlarm != null ? $arrAlarm : null,
                    ]
                ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            }  else {
                $response = json_encode(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function searchSholatFatimah(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'searchSholatByFatimah:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $resultAll = Cache::remember('searchSholatByFatimah:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($req) {
                
                $resultJadwalSholat = $req != null ? json_decode($this->getKodeKotaByNama($req)) : null;
                
                foreach($resultJadwalSholat->kota as $data){
                    $kode = $data->id;
                }

                $jadwal = json_decode($this->indoSholat($kode));

                return json_encode([
                    'statusCode' => '000', 
                    'message' => "Sukses",
                    'data' => [
                        'tanggal' => $jadwal->query, 'jadwal' => $jadwal->jadwal->data
                    ]
                ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            }  else {
                $response = json_encode(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function saveAlarm(Request $req)
    {
        $imei = $req->imei;
        $tgl = $req->tanggal;
        $lists = $req->lists;
        
        try {
            $cekData = SholatAlarms::where('imei',$imei)->where('tglWaktuSholat',$tgl)->first();
            $listsLama = $cekData != '' ? $cekData->listWaktuSholat : [];

            if ($cekData == '')
            {
                foreach ($lists as $value)
                {
                    $listsBaru[] = [
                        'idWaktuSholat' => $value['idWaktu'],//1:imsak,2:subuh,3:Dzuhur,4:Ashar,5:maghrib,6:isya//
                        'jamWaktuSholat' => $value['jamSholat'],
                        'menitWaktuSholat' => $value['menitSholat'],
                        'alarmWaktuSholat' => $value['alarmSholat']
                    ];
                }
                $dataSholatAlarm = new SholatAlarms;
                $dataSholatAlarm->imei = $imei;
                $dataSholatAlarm->tglWaktuSholat = $tgl;
                $dataSholatAlarm->listWaktuSholat = $listsBaru;
            } else {
                foreach ($lists as $value){
                    if (in_array($value['idWaktu'],array_column($listsLama,'idWaktuSholat')))
                    {
                        foreach($listsLama as $key => $data){
                            if ($data['idWaktuSholat'] == $value['idWaktu'])
                            {
                                $listsLama[$key]['jamWaktuSholat'] = $value['jamSholat'];
                                $listsLama[$key]['menitWaktuSholat'] = $value['menitSholat'];
                                $listsLama[$key]['alarmWaktuSholat'] = $value['alarmSholat'];
                            }
                        }
                    } else {
                        $listsLama[] = [
                            'idWaktuSholat' => $value['idWaktu'],//1:imsak,2:subuh,3:Dzuhur,4:Ashar,5:maghrib,6:isya//
                            'jamWaktuSholat' => $value['jamSholat'],
                            'menitWaktuSholat' => $value['menitSholat'],
                            'alarmWaktuSholat' => $value['alarmSholat']
                        ];
                    }
                }

                $dataSholatAlarm = SholatAlarms::where('imei',$imei)->where('tglWaktuSholat',$tgl)->first();
                $dataSholatAlarm->imei = $dataSholatAlarm->imei;
                $dataSholatAlarm->tglWaktuSholat = $dataSholatAlarm->tglWaktuSholat;
                $dataSholatAlarm->listWaktuSholat = $listsLama;
            }
            
            if ($dataSholatAlarm->save()) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '461', 'message' => 'Gagal Simpan Alarm Sholat']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getAlarmSholat($imei,$tgl)
    {
        try {
            $key = Str::of(Cache::get('key', 'getAlarmSholat:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $resultAll = Cache::remember('getAlarmSholat:' .$tgl.':'.$imei, env('CACHE_DURATION'), function () use ($imei,$tgl) {
                
                $data = SholatAlarms::where('imei',$imei)->where('tglWaktuSholat',$tgl)->first();
                $listAlarm = $data != '' ? $data->listWaktuSholat : [];
                
                return $listAlarm;
            });
            $response = $resultAll;
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
