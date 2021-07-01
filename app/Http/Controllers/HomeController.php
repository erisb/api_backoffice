<?php

namespace App\Http\Controllers;

use App\Articles;
use App\Artikel;
use App\Categories;
use App\UmrohPackage;
use App\UmrohToken;
use App\Notification;
use App\HijrahCarts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Controllers\APIEksternal\KalenderHijriyahController;
use App\Http\Controllers\APIEksternal\JadwalSholatController;
use App\Http\Controllers\APIEksternal\PergiUmrohController;
use App\Http\Controllers\APIEksternal\HijrahMerchantController;
use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuHomeController;
use App\Http\Controllers\LogTransactionController;
use App\Events\CacheFlushEvent;
use App\Events\PergiUmrohEvent;
use App\Events\UmrohTokenEvent;
use App\Events\CashTransactionEvent;
use App\Events\DueDateUmrohEvent;
use App\Events\TopupEvent;
use App\Events\TransferEvent;
use App\Events\CartsEvent;
use App\Events\NotificationEvent;
use App\Events\DonasiEvent;
use App\Events\FCMJumatEvent;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('onlyJson',['only'=>['homeResultAladhan','homeSearchAladhan','homeResultKemenag']]);
    }

    public function homeResultAladhan(Request $req)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $newArtikel     = new ArtikelController;
            $newKategori    = new CategoryController;
            $newMenuHome    = new MenuHomeController;
            $umroh          = new PergiUmrohController;
            $log            = new LogTransactionController;
            $mrc            = new HijrahMerchantController;

            $key = Str::of(Cache::get('key', 'apiHomeResultAladhan:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new DueDateUmrohEvent($timeNow));
            event(new CashTransactionEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new FCMJumatEvent($timeNow));


            $token = UmrohToken::select('created_at')->latest()->first();

            if ($token) {
                $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
                event(new UmrohTokenEvent($tokenTime));
            } else {
                $umroh->authenticationLogin();
            }

            $post = UmrohPackage::select('created_at')->latest()->first();

            if ($post) {
                $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
                event(new PergiUmrohEvent($time));
            } else {
                $umroh->packageInsert();
            }

            $resultAll = Cache::remember('apiHomeResultAladhan:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($newArtikel, $newKategori, $newMenuHome, $log, $mrc, $req) {
                $notif = Notification::where('idUserMobile', $req->idUserMobile)->where('read', 0)->count();
                $cart = HijrahCarts::where('idUserMobile', $req->idUserMobile)->count();
                $resultArtikel = $newArtikel->view();
                $resultKategori = $newKategori->getCategory();
                $resultmenuHome = $newMenuHome->getMenuHome();
                $merchant       = $mrc->homeMasjid($req);
                if ($notif > 0) {
                    return response()->json([
                        'statusCode'        => '000',
                        'message'           => 'Success',
                        'countNotif'        => $notif,
                        'countCart'         => $cart,
                        'categoryData'      => $resultKategori,
                        'menuHomeData'      => $resultmenuHome,
                        'masjid'            => $merchant != null ? $merchant['masjid'] : null,
                        'programUrgunsi'    => $merchant != null ? $merchant['programUrgunsi'] : null,
                        'historyTransaksi'  => $log->hitoryTransactionHome($req),
                        'aladhanData'       => json_decode($this->tglJadwalSholatAladhan($req)),
                    ]);
                } else {
                    return response()->json([
                        'statusCode'        => '000',
                        'message'           => 'Success',
                        'countNotif'        => $notif,
                        'countCart'         => $cart,
                        'categoryData'      => $resultKategori,
                        'menuHomeData'      => $resultmenuHome,
                        'masjid'            => $merchant != null ? $merchant['masjid'] : null,
                        'programUrgunsi'    => $merchant != null ? $merchant['programUrgunsi'] : null,
                        'historyTransaksi'  => $log->hitoryTransactionHome($req),
                        'aladhanData'       => json_decode($this->tglJadwalSholatAladhan($req)),
                    ]);
                }
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function homeSearchAladhan(Request $req)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $newArtikel = new ArtikelController;
            // $newKategori = new CategoryController;
            $newMenuHome = new MenuHomeController;

            $key = Str::of(Cache::get('key', 'apiHomeResultAladhan:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new CashTransactionEvent($timeNow));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::select('created_at')->latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $resultAll = Cache::remember('apiHomeResultAladhan:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($newArtikel, $newMenuHome, $req) {
                $notif = Notification::where('idUserMobile', $req->idUserMobile)->where('read', 0)->count();
                $cart = HijrahCarts::where('idUserMobile', $req->idUserMobile)->count();
                $resultArtikel = $newArtikel->view();
                // $resultKategori = $newKategori->searchCategory($req);
                $resultmenuHome = $newMenuHome->getMenuHome();
                if ($notif > 0) {
                    return response()->json([
                        'statusCode'    => '000',
                        'message'       => 'Success',
                        'countNotif'    => $notif,
                        'countCart'     => $cart,
                        // 'categoryData'  => $resultKategori,
                        'menuHomeData'  => $resultmenuHome,
                        'aladhanData'   => json_decode($this->tglJadwalSholatAladhan($req)),
                    ]);
                } else {
                    return response()->json([
                        'statusCode'    => '000',
                        'message'       => 'Success',
                        'countNotif'    => $notif,
                        'countCart'     => $cart,
                        // 'categoryData'  => $resultKategori,
                        'menuHomeData'  => $resultmenuHome,
                        'aladhanData'   => json_decode($this->tglJadwalSholatAladhan($req)),
                    ]);
                }
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function homeResultKemenag(Request $req)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $newKategori = new CategoryController;
            $newMenuHome = new MenuHomeController;

            $key = Str::of(Cache::get('key', 'apiHomeResultKemenag:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new CashTransactionEvent($timeNow));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::select('created_at')->latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $resultAll = Cache::remember('apiHomeResultKemenag:' . date('Y-m-d'), env('CACHE_DURATION'), function () use ($newKategori, $newMenuHome, $req) {
                $resultKategori = $newKategori->getCategory();
                $resultmenuHome = $newMenuHome->getMenuHome();
                return response()->json([
                    'statusCode' => '000',
                    'message' => 'Success',
                    'categoryData' => $resultKategori,
                    'menuHomeData' => $resultmenuHome,
                    'kemenagData' => json_decode($this->tglJadwalSholatKemenag($req)),
                ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function tglJadwalSholatKemenag(Request $req)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $newTgl = new KalenderHijriyahController();
            $newJadwalSholat = new JadwalSholatController();

            $key = Str::of(Cache::get('key', 'apiTglJadwal:' . $req->tanggal . ':' . $req->kode_kota))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new CashTransactionEvent($timeNow));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::select('created_at')->latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $resultAll = Cache::remember('apiTglJadwal:' . $req->tanggal . ':' . $req->kode_kota, env('CACHE_DURATION'), function () use ($newTgl, $newJadwalSholat, $req) {
                $resultTgl = $req != null ? $newTgl->hijriyah($req) : null;
                $resultJadwalSholat = $req != null ? json_decode($newJadwalSholat->indoSholatByDate($req)) : null;
                return json_encode([
                    'dataHijriyah' => [
                        'tgl' => $resultTgl->data->hijri->day,
                        'blnNumber' => $resultTgl->data->hijri->month->number,
                        'blnEn' => $resultTgl->data->hijri->month->en,
                        'thn' => $resultTgl->data->hijri->year,
                    ],
                    'dataJadwalSholat' => [
                        'jadwalSholat' => $resultJadwalSholat->data->jadwal
                    ]
                ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function tglJadwalSholatAladhan(Request $req)
    {
        $newJadwalSholat = new JadwalSholatController();
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $newTgl = new KalenderHijriyahController();
            $newJadwalSholat = new JadwalSholatController();

            $key = Str::of(Cache::get('key', 'apiTglJadwal:' . $req->latitude . ':' . $req->longitude . ':' . $req->date))->explode(':')[3];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new CashTransactionEvent($timeNow));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::select('created_at')->latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));
            $resultAll = Cache::remember('apiTglJadwal:' . $req->latitude . ':' . $req->longitude . ':' . $req->date, env('CACHE_DURATION'), function () use ($newTgl, $newJadwalSholat, $req) {
                $resultTgl = $req != null ? $newTgl->hijriyah($req) : null;
                $resultJadwalSholat = $req != null ? json_decode($newJadwalSholat->aladhanSholatByDate($req)) : null;
                $dataSholat =  $resultJadwalSholat != null ? $this->getDataSholat($resultJadwalSholat) : null;
                return json_encode([
                        'dataHijriyah' => [
                            'tgl' => $resultTgl->data->hijri->day,
                            'blnNumber' => $resultTgl->data->hijri->month->number,
                            'blnEn' => $resultTgl->data->hijri->month->en,
                            'thn' => $resultTgl->data->hijri->year,
                        ],
                        'dataJadwalSholat' => $dataSholat
                    ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                return response()->json(['statusCode' => '111', 'message' => 'Gagal Akses API', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    private function getDataSholat($data)
    {
        $arrBaru = [];

        $arrBaru = [$data->data->timings];
        $arrBaru['latitude'] = $data->data->meta->latitude;
        $arrBaru['longitude'] = $data->data->meta->longitude;

        return $arrBaru;
    }

    public function getCategoryById($id)
    {
        try {
            date_default_timezone_set("Asia/Jakarta");
            $timeNow = date("Y-m-d");

            $key = Str::of(Cache::get('key', 'detail_category:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));
            event(new DueDateUmrohEvent($timeNow));
            event(new CartsEvent($timeNow));
            event(new NotificationEvent($timeNow));
            event(new DonasiEvent($timeNow));
            event(new TopupEvent($timeNow));
            event(new TransferEvent($timeNow));
            event(new CashTransactionEvent($timeNow));

            $post = UmrohPackage::select('created_at')->latest()->first();

            $time = date('Y-m-d h:i:sa', strtotime('+1 hour', strtotime($post->created_at)));
            event(new PergiUmrohEvent($time));

            $token = UmrohToken::select('created_at')->latest()->first();

            $tokenTime = date('Y-m-d', strtotime('+13 day', strtotime($token->created_at)));
            event(new UmrohTokenEvent($tokenTime));

            $cache = Cache::remember('detail_category:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                return Categories::where('_id', $id)->first();
            });
            if ($cache) {
                $arr = [];
                // foreach ($cache as $data) {
                array_push($arr, ['categoryName' => $cache->categoryName, 'articles' => $cache->artikel()]);
                // }
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
