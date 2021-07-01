<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Storage;
use App\SurahQurans;
use App\HistoryQurans;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class QuranController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['updateSurah','updateAyat']]);
        $this->middleware('onlyJson',['only'=>['searchSurah','searchJuz','addLastSurah','addLastJuz','getLastQurans']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function getSurah()
    {
        try {
            $key = Str::of(Cache::get('key','surah:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('surah:'.date('Y-m-d'), env('CACHE_DURATION'), function () {
                $surah = SurahQurans::orderBy('noSurah','asc')->get();
                foreach ($surah as $values) {
                    $arrBaru[] = [
                                    'idQuran' => $values->_id,
                                    'noSurah' => $values->noSurah,
                                    'namaSurah' => $values->namaSurah,
                                    'artiIndo' => $values->artiIndo,
                                    'artiEng' => $values->artiEng,
                                    'namaArab' => $values->namaArab,
                                    'kategoriSurah' => $values->kategoriSurah,
                                    'jmlhAyat' => $values->jmlhAyat,
                                    'bismillah' => $values->bismillah
                                ];
                }
                return $arrBaru;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function searchSurah(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','surah:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('surah:'.date('Y-m-d').$req->search, env('CACHE_DURATION'), function () use ($req) {
                $surah = SurahQurans::where('namaSurah','like',"%".$req->search."%")->get();
                $arrBaru = [];
                foreach ($surah as $values) {
                    $arrBaru[] = [
                                    'noSurah' => $values->noSurah,
                                    'namaSurah' => $values->namaSurah,
                                    'artiIndo' => $values->artiIndo,
                                    'artiEng' => $values->artiEng,
                                    'namaArab' => $values->namaArab,
                                    'kategoriSurah' => $values->kategoriSurah,
                                    'jmlhAyat' => $values->jmlhAyat,
                                    'bismillah' => $values->bismillah
                                ];
                }
                return $arrBaru;
            });
            
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function getAyat($surah)
    {
        try {
            $key = Str::of(Cache::get('key','searchAyat:'.date('Y-m-d').':'.(int)$surah))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('searchAyat:'.date('Y-m-d').':'.(int)$surah, env('CACHE_DURATION'), function () use ($surah) {
                $ayatAll = SurahQurans::where('noSurah',(int)$surah)->get(['dataAyat', 'namaSurah', 'noSurah']);
                foreach ($ayatAll as $values) {
                    $arrBaru[] = $values->dataAyat;
                    $noJuz = $values->dataAyat[0]['noJuz'];
                    $namaSurah = $values->namaSurah;
                    $noSurah = $values->noSurah;
                }
                $ayatPertamaFatihah = SurahQurans::where('noSurah',(int)1)->get(['dataAyat']);
                foreach ($ayatPertamaFatihah as $values)
                {
                    $ayatBismillah = $values->dataAyat[0]['ayatArab'];
                    $artiIndoBismillah = $values->dataAyat[0]['artiIndoAyat'];
                    $artiEngBismillah = $values->dataAyat[0]['artiEngAyat'];
                    $audioBismillah = $values->dataAyat[0]['audio'];
                }
                $size = count($arrBaru);
                if ($noSurah == 1 || $noSurah == 9) {
                    $next = [];
                } else {
                    $next[] = [
                                "noJuz" => $noJuz,
                                "noAyat" => '0',
                                "namaSurah" => $namaSurah,
                                "ayatArab" => $ayatBismillah,
                                "artiIndoAyat" => $artiIndoBismillah,
                                "artiEngAyat" => $artiEngBismillah,
                                "audio" => $audioBismillah
                            ];
                }
                for($i=0;$i<$size;$i++)
                {
                    foreach ($arrBaru[$i] as $index => $datas)
                    {
                        $data = 
                        [
                            "noJuz" => $arrBaru[$i][$index]['noJuz'],
                            "noAyat" => $arrBaru[$i][$index]['noAyat'],
                            "namaSurah" => $namaSurah,
                            "ayatArab" => $arrBaru[$i][$index]['ayatArab'],
                            "artiIndoAyat" => $arrBaru[$i][$index]['artiIndoAyat'],
                            "artiEngAyat" => $arrBaru[$i][$index]['artiEngAyat'],
                            "audio" => $arrBaru[$i][$index]['audio']
                        ];
                        array_push($next,$data);
                    }
                }
                return $next;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function getJuz()
    {
        try {
            $key = Str::of(Cache::get('key','juz:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('juz:'.date('Y-m-d'), env('CACHE_DURATION'), function () {
                $arrAyat = [];
                $arrTemp = [];
                $dataSurah = SurahQurans::orderBy('noSurah','asc')->orderBy('dataAyat.noJuz','asc')->orderBy('dataAyat.noAyat','asc')->get();
                for ($i = 0; $i < count($dataSurah); $i++) {
                    for ($j = 0; $j < count($dataSurah[$i]['dataAyat']); $j++)
                    {
                        if (!in_array($dataSurah[$i]['dataAyat'][$j]['noJuz'],$arrTemp))
                        {
                            $data = ['idQuran' => $dataSurah[$i]['_id'],'noJuz' => $dataSurah[$i]['dataAyat'][$j]['noJuz'],'namaSurah' => $dataSurah[$i]['namaSurah'],'namaArab' => $dataSurah[$i]['namaArab'],'noAyat' => $dataSurah[$i]['dataAyat'][$j]['noAyat']];
                            array_push($arrAyat,$data);
                        }
                        array_push($arrTemp,$dataSurah[$i]['dataAyat'][$j]['noJuz']);
                    }
                    
                }
                return $arrAyat;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function searchJuz(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key','juz:'.date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('juz:'.date('Y-m-d').$req->search, env('CACHE_DURATION'), function () use($req){
                $arrAyat = [];
                $arrTemp = [];
                $dataSurah = SurahQurans::orderBy('noSurah','asc')->orderBy('dataAyat.noJuz','asc')->orderBy('dataAyat.noAyat','asc')->get();
                for ($i = 0; $i < count($dataSurah); $i++) {
                    for ($j = 0; $j < count($dataSurah[$i]['dataAyat']); $j++)
                    {
                        if ((int) $req->search == $dataSurah[$i]['dataAyat'][$j]['noJuz']) {
                            if (!in_array($dataSurah[$i]['dataAyat'][$j]['noJuz'],$arrTemp))
                            {
                                $data = ['noJuz' => $dataSurah[$i]['dataAyat'][$j]['noJuz'],'namaSurah' => $dataSurah[$i]['namaSurah'],'namaArab' => $dataSurah[$i]['namaArab'],'noAyat' => $dataSurah[$i]['dataAyat'][$j]['noAyat']];
                                array_push($arrAyat,$data);
                            }
                            array_push($arrTemp,$dataSurah[$i]['dataAyat'][$j]['noJuz']);
                        }
                    }
                    
                }
                return $arrAyat;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function searchSurahByJuz($juz)
    {
        try {
            $key = Str::of(Cache::get('key','surahByJuz:'.date('Y-m-d').':'.$juz))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('surahByJuz:'.date('Y-m-d').':'.$juz, env('CACHE_DURATION'), function () use ($juz){
                $dataAyat = SurahQurans::orderBy('noSurah','asc')->get();
                foreach ($dataAyat as $index => $datas)
                {
                    foreach ($datas['dataAyat'] as $values)
                    {
                        $arrBaru[] = [
                                'namaSurah' => $datas['namaSurah'],
                                'noJuz' => $values['noJuz'],
                                'noAyat' => $values['noAyat'],
                                'ayatArab' => $values['ayatArab'],
                                'artiIndoAyat' => $values['artiIndoAyat'],
                                'artiEngAyat' => $values['artiEngAyat'],
                                'audio' => $values['audio'],
                        ];
                    }
                }
                
                foreach ($arrBaru as $key => $values)
                {
                    if(array_key_exists('noJuz',$arrBaru[$key]))
                    {
                        $arrJuz[$arrBaru[$key]['noJuz']][] = $arrBaru[$key];
                    }
                    else {
                        $arrJuz[] = $arrBaru[$key];
                    }
                }

                return $arrJuz[$juz];
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Berhasil', 'data' => $cache]);
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function getDataQuranFirstPage($take)
    {
        try {
            $results = SurahQurans::skip(0)->take((int)$take)->orderBy('_id','asc')->get();
            $totalData = SurahQurans::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results, 'total' => $totalData]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataQuranByPage($take,$page)
    {
        $skip = ($take*$page)-$take;
        try {
            $results = SurahQurans::skip($skip)->take((int)$take)->orderBy('_id','asc')->get();
            $totalData = SurahQurans::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results, 'total' => $totalData]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataQuranBySearch(Request $req)
    {
        $val = str_replace(' ','',$req->search);
        try {
            $results = SurahQurans::where('namaSurah','like','%'.$val.'%')->orderBy('_id','asc')->get();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function updateSurah(Request $req, $id)
    {
        $data = SurahQurans::where('_id', $id)->first();
        try {
            $data->namaSurah =  $req->namaSurah;
            $data->namaArab =  $req->namaArab;
            $data->artiIndo =  $req->artiIndo;
            $data->artiEng =  $req->artiEng;
            $data->kategoriSurah =  $req->kategoriSurah;
            $data->jmlhAyat =  $req->jmlhAyat;
            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Surah Quran"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Quran','Update Surah Quran - '.$req->namaSurah,json_decode($response)->message));
        return $response;
    }

    public function updateAyat(Request $req, $id)
    {
        $data = SurahQurans::where('_id',$id)->first();
        try {
            $panjangData = count($data->dataAyat);
            $arrBaru = [];
            for($i=0;$i<$panjangData;$i++){
                if ($data->dataAyat[$i]['noJuz'] == $req->noJuz && $data->dataAyat[$i]['noAyat'] == $req->noAyat)
                {
                    $arrBaru[] = [
                        'noJuz' => $req->noJuz,
                        'noAyat' => $req->noAyat,
                        'ayatArab' => $req->ayatArab,
                        'artiIndoAyat' => $req->artiIndoAyat,
                        'artiEngAyat' => $req->artiEngAyat,
                        'audio' => $req->audio,
                    ];
                }
                else {
                    $arrBaru[] = [
                        'noJuz' => $data->dataAyat[$i]['noJuz'],
                        'noAyat' => $data->dataAyat[$i]['noAyat'],
                        'ayatArab' => $data->dataAyat[$i]['ayatArab'],
                        'artiIndoAyat' => $data->dataAyat[$i]['artiIndoAyat'],
                        'artiEngAyat' => $data->dataAyat[$i]['artiEngAyat'],
                        'audio' => $data->dataAyat[$i]['audio'],
                    ];
                }
            }
            $data->dataAyat = $arrBaru;
            if ($data->save()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '334', 'message' => "Gagal Update Ayat Quran"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Quran','Update Ayat Quran - Juz - '.$req->noJuz.' - Ayat - '.$req->noAyat,json_decode($response)->message));
        return $response;
    }

    public function addLastSurah(Request $req)
    {
        $count = HistoryQurans::where(['idQuran'=> $req->idQuran, 'imei' => $req->imei, 'no'=> $req->no])->count();
        try {
            if ($count > 0) {
                $data = HistoryQurans::where(['idQuran'=> $req->idQuran, 'imei' => $req->imei, 'no'=> $req->no])->first();
            
                $data->ayat_position    = $req->position;

                if ($data->update()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '335', 'message' => "Gagal Insert Last Surah"));
                }
                
            }else {
                $data = new HistoryQurans;
            
                $data->idQuran          = $req->idQuran;
                $data->imei             = $req->imei;
                $data->no               = $req->no;
                $data->type             = $req->type;
                $data->ayat_position    = $req->position;
                if ($data->save()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '335', 'message' => "Gagal Insert Last Surah"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function addLastJuz(Request $req)
    {
        
        $count = HistoryQurans::where(['idQuran'=> $req->idQuran, 'imei' => $req->imei, 'no'=> $req->no])->count();
        try {
            if ($count > 0) {
                $data = HistoryQurans::where(['idQuran'=> $req->idQuran, 'imei' => $req->imei, 'no'=> $req->no])->first();
            
                $data->ayat_position    = $req->position;
                
                if ($data->update()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '335', 'message' => "Gagal Insert Last Juz"));
                }
                
            }else {
                $data = new HistoryQurans;
            
                $data->idQuran          = $req->idQuran;
                $data->imei             = $req->imei;
                $data->no               = $req->no;
                $data->type             = $req->type;
                $data->ayat_position    = $req->position;
                if ($data->save()) {
                    $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
                } else {
                    $response = json_encode(array('statusCode' => '335', 'message' => "Gagal Insert Last Surah"));
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getLastQurans(Request $req)
    {    
        try {
            $data = HistoryQurans::where('imei', $req->imei)->get();
            $arr = [];
            foreach ($data as $value) {
                $qurans = SurahQurans::where('_id', $value->idQuran)->first();
                array_push($arr, [
                    'idHistory' => $value->_id,
                    'idQuran'   => $value->idQuran,
                    'no' => $value->no,
                    'type' => $value->type,
                    'namaSurah' => $qurans->namaSurah,
                    'artiIndo' => $qurans->artiIndo,
                    'artiEng' => $qurans->artiEng,
                    'namaArab' => $qurans->namaArab,
                    'kategoriSurah' => $qurans->kategoriSurah,
                    'ayatPosition' => $value->ayat_position,
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

    public function delLastQurans($id)
    {
        try {
            $data = '';
            $data = HistoryQurans::where('_id', $id)->first();
            if ($data->delete()) {
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '337', 'message' => "Gagal Delete Last Qurans"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    // public function updateQuran(Request $req)
    // {
    //     return $req['10']['number'];
    // }

    // public function uploadAyat(Request $req,$id){
        
    //     if ($req->hasFile('file'))
    //     {
    //         $file = $req->file('file');
    //         // File Details 
    //         $filename = $file->getClientOriginalName();
    //         $extension = $file->getClientOriginalExtension();
    //         // $tempPath = $file->getRealPath();
    //         // $fileSize = $file->getSize();
    //         // $mimeType = $file->getMimeType();
            
    //         $dataSurah = SurahQurans::where('_id',$id)->first();
    //         if (count($dataSurah->dataAyat) != 0)
    //         {
    //             $dataSurah->dataAyat = [];
    //         }
    //         $dataSurah->save();

    //         // Valid File Extensions
    //         $valid_extension = array("csv");
    //         if(in_array(strtolower($extension),$valid_extension)){
                
    //             // File upload location
    //             $location = storage_path("app/uploads/");
    //             // Upload file
    //             $file->move($location,$filename);

    //             // Import CSV to Database
    //             $filepath = storage_path("app/uploads/".$filename);

    //             //Reading file
    //             $file_ayat = fopen($filepath,"r");

    //             $importData_arr = array();
    //             $i = 0;

    //             while (($filedata = fgetcsv($file_ayat, 100000, ";","'")) !== FALSE) {
    //                 $num = count($filedata );
                    
    //                 // Skip first row (Remove below comment if you want to skip the first row)
    //                 /*if($i == 0){
    //                     $i++;
    //                     continue; 
    //                 }*/
    //                 for ($c=0; $c < $num; $c++) {
    //                     $importData_arr[$i][] = $filedata [$c];
    //                 }
    //                 $i++;
    //             }
    //             fclose($file_ayat);
    //             // var_dump($importData_arr);die;
    //             $arrBaru = [];
    //             for($j=0;$j<count($importData_arr);$j++)
    //             {
    //                 // if ($importData_arr[$j][6] == $id){
    //                     $arrBaru[] = [
    //                         'noJuz' => str_replace('"','',$importData_arr[$j][0]),
    //                         'noAyat' => $importData_arr[$j][1],
    //                         'ayatArab' => $importData_arr[$j][2],
    //                         'artiIndoAyat' => $importData_arr[$j][3],
    //                         'artiEngAyat' => $importData_arr[$j][4],
    //                         'audio' => str_replace('"','',$importData_arr[$j][5]),
    //                     ];
    //                 }
    //             }
    //             $dataSurah->dataAyat = $arrBaru;
    //             if ($dataSurah->save())
    //             {
    //                 return response()->json(['statusCode' => '000', 'message' => 'Sukses Upload']);
    //             } else {
    //                 return response()->json(['statusCode' => '888', 'message' => 'Gagal Upload']);
    //             }
    //         }
    //         else {
    //             return response()->json(['statusCode' => '889', 'message' => 'Harus CSV']);
    //         }
    //     } else {
    //         return response()->json(['statusCode' => '890', 'message' => 'File tidak ditemukan']);
    //     }
        
        
    // }

}
