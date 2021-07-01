<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
    //return 'tes bro';
});

$router->group(['prefix' => 'apihijrah/v2'], function () use ($router) {

    //Start API Mobile//

    //Login
    $router->post('login', 'Auth\LoginController@login');
    //Logout
    $router->post('logout', 'Auth\LoginController@logout');
    //cekOTP
    $router->post('otp/validasi', 'Auth\CheckValidation@cekValidasiOtp');
    $router->post('gateway/resendOtp', 'Auth\LoginController@updateOtp');
    //cekPIN
    $router->post('pin/validasi', 'Auth\CheckValidation@cekValidasiPin');
    //savePIN
    $router->put('pin', 'Auth\LoginController@savePin');
    //saveTelp
    $router->post('telp', 'Auth\LoginController@saveNoTelp');
    $router->post('reSend', 'Auth\LoginController@updateOtp');
    //save Log Guest
    $router->post('logguest', 'Auth\LoginController@saveLogGuest');

    //register
    $router->post('register', 'Auth\RegisterController@saveRegister');

    //Update Imei
    $router->put('imei', 'Auth\LoginController@updateImei');

    //forgot pin
    $router->post('forgotpin/otp', 'Auth\ForgotPinController@forgotPinSendOTP');

    //kategori
    $router->get('categories', 'CategoryController@getCategory');

    //Artikel
    $router->get('article/view', 'ArtikelController@viewAll');
    $router->get('article/{id}', 'ArtikelController@viewDetail');
    $router->get('article/view', 'ArtikelController@view');
    $router->post('articles', 'ArtikelController@getArtikel');

    // Inspirasi
    $router->get('inspiration/{id}', 'InspirationController@viewDetail');
    $router->get('inspiration', 'InspirationController@getInspirasi');

    // Penceramah
    $router->get('lecturer/{id}', 'LecturerController@viewDetail');
    $router->post('search/lecturer', 'LecturerController@search');
    $router->get('lecturer', 'LecturerController@getLecturer');

    //MenuHome
    $router->get('menuhome', 'MenuHomeController@getMenuHome');
    $router->get('menuhome/{id}', 'MenuHomeController@viewDetail');

    //API Kalender Hijriyah
    $router->get('apieksternal/kalender', 'APIEksternal\KalenderHijriyahController@hijriyah');

    //API Jadwal Sholat
    $router->get('apieksternal/kemenagprov', 'APIEksternal\JadwalSholatController@kemenagProv');
    $router->get('apieksternal/kemenagkab', 'APIEksternal\JadwalSholatController@kemenagKab');
    $router->get('apieksternal/kemenagsholat', 'APIEksternal\JadwalSholatController@kemenagSholat');
    $router->get('apieksternal/aladhansholat', 'APIEksternal\JadwalSholatController@aladhanSholat');
    $router->get('apieksternal/homejadwalsholatindo', 'APIEksternal\JadwalSholatController@indoSholatByDate');
    $router->get('apieksternal/kodekota', 'APIEksternal\JadwalSholatController@getKodeKotaByNama');
    $router->get('apieksternal/kota', 'APIEksternal\JadwalSholatController@getKotaFatimah');
    $router->get('apieksternal/jadwalsholatindobydate', 'APIEksternal\JadwalSholatController@indoSholatByDate');
    $router->post('apieksternal/jadwalsholatluarbydate', 'APIEksternal\JadwalSholatController@aladhanBydate');
    $router->get('apieksternal/sholataladhanbykota', 'APIEksternal\JadwalSholatController@aladhanSholatByCity');
    $router->get('apieksternal/sholatfatimahbykota', 'APIEksternal\JadwalSholatController@searchSholatFatimah');
    $router->post('apieksternal/jadwalsholat/alarm', 'APIEksternal\JadwalSholatController@saveAlarm');
    $router->get('apieksternal/jadwalsholat', 'APIEksternal\JadwalSholatController@aladhanSholatByDate');

    //API Home
    $router->get('home/tgljadwalsholatkemenag', 'HomeController@tglJadwalSholatKemenag');
    $router->get('home/tgljadwalsholataladhan', 'HomeController@tglJadwalSholatAladhan');
    $router->post('home/kemenag', 'HomeController@homeResultKemenag');
    $router->post('home/aladhan', 'HomeController@homeResultAladhan');
    $router->post('home/aladhan/search', 'HomeController@homeSearchAladhan');
    $router->get('home/category/{id}', 'HomeController@getCategoryById');
    $router->get('home/searchSholat', 'HomeController@searchSholatByKota');

    //API Kiblat
    $router->get('apieksternal/kiblat', 'APIEksternal\KiblatController@arah');

    //API Masjid
    $router->post('apieksternal/masjid', 'APIEksternal\MasjidController@lokasi');
    $router->post('apieksternal/masjid/cari', 'APIEksternal\MasjidController@cari');

    //API Restoran
    $router->post('apieksternal/restoran', 'APIEksternal\RestoranController@lokasi');

    //API Quran
    $router->get('quran/ayat/{surah}', 'QuranController@getAyat');
    $router->get('quran/surah', 'QuranController@getSurah');
    $router->post('quran/surah/search', 'QuranController@searchSurah');
    $router->get('quran/juz', 'QuranController@getJuz');
    $router->post('quran/juz/search', 'QuranController@searchJuz');
    $router->get('quran/juz/{juz}', 'QuranController@searchSurahByJuz');
    $router->post('quran/hist/surah', 'QuranController@addLastSurah');
    $router->post('quran/hist/juz', 'QuranController@addLastJuz');
    $router->post('quran/hist', 'QuranController@getLastQurans');
    $router->delete('quran/hist/{id}', 'QuranController@delLastQurans');

    //API GatewayController
    $router->get('gateway/send/{noTelp}/{code}', 'APIEksternal\GatewayController@smsSend');
    $router->get('gateway/email', 'APIEksternal\GatewayController@emailSend');
    $router->get('gateway/zenziva/{noTelp}/{code}', 'APIEksternal\GatewayController@zenzivaSms');
    $router->get('gateway/zenziva/{noTelp}', 'APIEksternal\GatewayController@subS');

    //Profile
    $router->put('profile/telp/{id}', 'ProfilController@updateNoTelpProfile');
    $router->post('profile/foto/{id}', 'ProfilController@updateFotoProfile');
    $router->put('profile/pin/{id}', 'ProfilController@updatePinProfile');
    $router->get('profile/{id}', 'ProfilController@getDataUserById');
    $router->put('profile/name/{id}', 'ProfilController@updateNameProfile');
    $router->put('profile/email/{id}', 'ProfilController@updateEmailProfile');

    //KYC / Know Your Customer
    $router->put('profile/ktp/{id}', 'ProfilController@updateNikProfile');
    $router->put('profile/data/{id}', 'ProfilController@updateDataProfile');
    $router->post('profile/fotoKtp/{id}', 'ProfilController@updateFotoKtpProfile');
    $router->post('profile/selfieKtp/{id}', 'ProfilController@updateFotoSelfieKtpProfile');

    //Term & Conditions
    $router->get('termconditions', 'TermConditionController@getData');

    //PrivacyPolicies
    $router->get('privacyPolicies', 'PrivacyPoliciesController@view');

    //Doa
    $router->get('doa', 'DoaController@view');
    $router->post('search/doa', 'DoaController@search');
    $router->post('doa/{id}', 'DoaController@detail');

    //pergi umroh
    $router->get('apieksternal/pergiumroh', 'APIEksternal\PergiUmrohController@authenticationLogin');
    $router->get('apieksternal/pergiumroh/package_insert', 'APIEksternal\PergiUmrohController@packageInsert');
    $router->post('apieksternal/pergiumroh/packages', 'APIEksternal\PergiUmrohController@package');
    $router->get('apieksternal/pergiumroh/packages/home', 'APIEksternal\PergiUmrohController@packageHome');
    $router->get('apieksternal/pergiumroh/packages/{id}', 'APIEksternal\PergiUmrohController@packageById');
    $router->put('apieksternal/pergiumroh/umroh/{id}', 'APIEksternal\PergiUmrohController@umrohCreate');
    $router->get('apieksternal/pergiumroh/umrohDetail/{id}', 'APIEksternal\PergiUmrohController@umrohDetail');
    $router->get('apieksternal/pergiumroh/umrohPayment', 'APIEksternal\PergiUmrohController@umrohPay');
    $router->get('apieksternal/pergiumroh/softBook', 'APIEksternal\PergiUmrohController@softBook');
    $router->get('apieksternal/pergiumroh/umrohStock', 'APIEksternal\PergiUmrohController@umrohStock');
    $router->post('apieksternal/pergiumroh/room', 'APIEksternal\PergiUmrohController@insertRoom');
    $router->post('apieksternal/pergiumroh/dataUmroh/{id}', 'APIEksternal\PergiUmrohController@updateUmroh');
    $router->get('apieksternal/pergiumroh/umrohPenuh/{id}', 'APIEksternal\PergiUmrohController@pembayaranPenuh');
    $router->get('apieksternal/pergiumroh/umrohKredit/{id}', 'APIEksternal\PergiUmrohController@pembayaranKredit');
    $router->post('apieksternal/pergiumroh/ktp', 'APIEksternal\PergiUmrohController@insertKtp');
    $router->post('apieksternal/pergiumroh/kk', 'APIEksternal\PergiUmrohController@insertKk');
    $router->post('apieksternal/pergiumroh/bukuNikah', 'APIEksternal\PergiUmrohController@insertBn');
    $router->post('apieksternal/pergiumroh/bukuMiningitis', 'APIEksternal\PergiUmrohController@insertBm');
    $router->post('apieksternal/pergiumroh/buktiBayar/{id}', 'APIEksternal\PergiUmrohController@buktiBayar');
    $router->get('apieksternal/pergiumroh/listPayment/{id}', 'APIEksternal\PergiUmrohController@listPayment');
    $router->get('apieksternal/pergiumroh/byCity', 'APIEksternal\PergiUmrohController@searchCity');
    $router->get('apieksternal/pergiumroh/byPrice', 'APIEksternal\PergiUmrohController@searchPrice');
    $router->get('apieksternal/pergiumroh/byDate', 'APIEksternal\PergiUmrohController@searchDate');
    $router->get('apieksternal/pergiumroh/status/{id}', 'APIEksternal\PergiUmrohController@checkStatus');
    $router->get('apieksternal/pergiumroh/ticket/{id}', 'APIEksternal\PergiUmrohController@cetakTiket');
    $router->get('apieksternal/pergiumroh/promo', 'APIEksternal\PergiUmrohController@promosion');
    $router->get('apieksternal/pergiumroh/promo/{id}', 'APIEksternal\PergiUmrohController@promosionById');
    $router->get('apieksternal/pergiumroh/promoPackages/{id}', 'APIEksternal\PergiUmrohController@promosionById');
    $router->post('apieksternal/pergiumroh/transactionHistory', 'APIEksternal\PergiUmrohController@transactionHistory');
    $router->get('apieksternal/pergiumroh/transactionHistory/{id}', 'APIEksternal\PergiUmrohController@transactionHistDetail');
    $router->get('apieksternal/pergiumroh/syaratKetentuan', 'APIEksternal\PergiUmrohController@syaratKetentuan');
    $router->get('apieksternal/pergiumroh/dueDate', 'APIEksternal\PergiUmrohController@dueDate');
    $router->post('apieksternal/pergiumroh/carts', 'APIEksternal\PergiUmrohController@addCarts');
    $router->delete('apieksternal/pergiumroh/carts/{id}', 'APIEksternal\PergiUmrohController@destroyCarts');
    $router->post('apieksternal/pergiumroh/carts/view', 'APIEksternal\PergiUmrohController@getCart');
    $router->get('apieksternal/pergiumroh/ordercart/{id}', 'APIEksternal\PergiUmrohController@orderCart');
    $router->get('apieksternal/pergiumroh/notifikasi', 'APIEksternal\PergiUmrohController@notifikasi');
    $router->get('generate', 'APIEksternal\PergiUmrohController@generate');

    //fcm
    $router->post('apieksternal/fcm/token/with', 'APIEksternal\FCMController@getTokenWithLogin');
    $router->post('apieksternal/fcm/token/without', 'APIEksternal\FCMController@getTokenWithoutLogin');
    $router->post('apieksternal/fcm/message/azan', 'APIEksternal\FCMController@sendMessageAzan');
    // $router->post('apieksternal/fcm/message/{time}', 'APIEksternal\FCMController@sendMessageJumatan');
    $router->post('notifications', 'NotificationController@getNotification');
    $router->delete('notifications/detail', 'NotificationController@destroy');

    //bookmark
    $router->post('bookmark/doa/view', 'BookmarkController@getBookmark');
    $router->post('bookmark/doa', 'BookmarkController@insert');
    $router->delete('bookmark/doa/{id}', 'BookmarkController@destroy');

    //MAIL
    $router->get('mail/{id}', 'Mail\HijrahEmailController@sendEmailSetorTarik');

    //EXPORT EXCEL
    $router->get('excel/umroh', 'ExportExcelController@ExportUmroh');

    // MOBILE PULSA
    $router->get('apieksternal/mp/pulsa', 'APIEksternal\MobilePulsaController@mobileListPulsa');
    $router->get('apieksternal/mp/data', 'APIEksternal\MobilePulsaController@mobileListData');
    $router->get('apieksternal/mp/etoll', 'APIEksternal\MobilePulsaController@mobileListEtoll');
    $router->get('apieksternal/mp/dana', 'APIEksternal\MobilePulsaController@mobileListDana');
    $router->get('apieksternal/mp/shopee', 'APIEksternal\MobilePulsaController@mobileListShopeePay');
    $router->get('apieksternal/mp/ovo', 'APIEksternal\MobilePulsaController@mobileListOvo');
    $router->get('apieksternal/mp/linkaja', 'APIEksternal\MobilePulsaController@mobileListLinkAja');
    $router->get('apieksternal/mp/gopay', 'APIEksternal\MobilePulsaController@mobileListGopay');
    $router->get('apieksternal/mp/pln', 'APIEksternal\MobilePulsaController@mobileListPln');
    $router->post('apieksternal/mp/pln/inquiry/{hp}', 'APIEksternal\MobilePulsaController@inquiryPrepaidPln');
    $router->post('apieksternal/mp/checkStatus', 'APIEksternal\MobilePulsaController@mobileListCheckStatusTransaksi');
    $router->post('apieksternal/mp/topup/{id}', 'APIEksternal\MobilePulsaController@mobileListTopUp');
    $router->get('apieksternal/mp/pasca/bpjs', 'APIEksternal\MobilePulsaController@mobileListBpjs');
    $router->get('apieksternal/mp/pasca/pdam', 'APIEksternal\MobilePulsaController@mobileListPdam');
    $router->post('apieksternal/mp/pasca/pdamByProvince', 'APIEksternal\MobilePulsaController@mobileListPdamByProvince');
    $router->post('apieksternal/mp/pasca/inquiryBpjs', 'APIEksternal\MobilePulsaController@listInquiryBpjsKesehatan');
    $router->post('apieksternal/mp/pasca/inquiryPdam', 'APIEksternal\MobilePulsaController@listInquiryPdam');
    $router->post('apieksternal/mp/pasca/inquiryTelepon', 'APIEksternal\MobilePulsaController@listInquiryTelepon');
    $router->post('apieksternal/mp/pasca/inquiryPln', 'APIEksternal\MobilePulsaController@listInquiryPlnPasca');
    $router->post('apieksternal/mp/pasca/pay/{id}', 'APIEksternal\MobilePulsaController@mobilePayPasca');
    $router->get('apieksternal/mp/pasca/status', 'APIEksternal\MobilePulsaController@mobileCheckStatusTransaksiPasca');
    $router->post('apieksternal/mp/callback/prePaid', 'APIEksternal\MobilePulsaController@callbackPrePaid');
    $router->get('apieksternal/mp/ref/{amount}/{date}', 'APIEksternal\MootaController@getDataMootaForMp');

    //Moota
    $router->post('apieksternal/callback/moota', 'APIEksternal\MootaController@webhookMoota');
    $router->post('apieksternal/callback/push/moota', 'APIEksternal\MootaController@callbackWebhookMoota');
    $router->get('apieksternal/callback/moota/{id}', 'APIEksternal\MootaController@getDataMoota');
    $router->get('apieksternal/moota/profile', 'APIEksternal\MootaController@profile');
    $router->get('apieksternal/moota/balance', 'APIEksternal\MootaController@balance');
    $router->get('apieksternal/moota/bank', 'APIEksternal\MootaController@bankAccount');
    $router->get('apieksternal/moota/bank/{id}', 'APIEksternal\MootaController@bankDetail');
    $router->get('apieksternal/moota/mutation', 'APIEksternal\MootaController@mutation');
    $router->get('apieksternal/moota/lastMutation/{id}', 'APIEksternal\MootaController@lastMutation');
    $router->post('apieksternal/moota/searchAmount', 'APIEksternal\MootaController@searchByAmount');
    $router->get('apieksternal/moota/searchDesc', 'APIEksternal\MootaController@searchByDesc');

    //Iluma
    $router->post('apieksternal/cek/npwp', 'APIEksternal\IlumaController@cekNPWP');
    $router->post('apieksternal/cek/ktp', 'APIEksternal\IlumaController@cekKTP');
    $router->post('apieksternal/cek/bank', 'APIEksternal\IlumaController@cekBank');
    $router->post('apieksternal/callback/npwp', 'APIEksternal\IlumaController@callBackNpwp');
    $router->post('apieksternal/callback/bank', 'APIEksternal\IlumaController@callBackBank');
    $router->get('apieksternal/callback/token', 'APIEksternal\IlumaController@getToken');

    //OY
    $router->post('apieksternal/oy/accountInquiry', 'APIEksternal\OyController@accountInquiry');
    $router->post('apieksternal/oy/transfer', 'APIEksternal\OyController@disbursement');
    $router->post('apieksternal/oy/checkStatus', 'APIEksternal\OyController@disbursementStatus');
    $router->get('apieksternal/oy/balance', 'APIEksternal\OyController@disbursementBalance');
    $router->post('apieksternal/oy/status', 'APIEksternal\OyController@callbackDisbursement');
    $router->get('apieksternal/oy/money', 'APIEksternal\OyController@inOutMoney');
    $router->get('apieksternal/oy/money/info', 'APIEksternal\OyController@transactionInfo');
    $router->get('partnerTrxId', 'APIEksternal\OyController@partnerTrxId');
    $router->get('apieksternal/oy/cashTransaction', 'APIEksternal\OyController@inOutMoney');
    $router->get('apieksternal/oy/transactionInfo/{id}', 'APIEksternal\OyController@transactionInfo');
    $router->post('apieksternal/oy/setortarik', 'APIEksternal\OyController@callbackSetorTarik');
    $router->get('apieksternal/oy/refreshCode/{id}', 'APIEksternal\OyController@refreshCode');
    $router->post('apieksternal/merchant/callback/disbursement', 'APIEksternal\OyController@disbursementMerchent');
    $router->post('apieksternal/merchant/callback/disbursement/{id}', 'APIEksternal\OyController@disbursementSetorTarik');

    //Hijrah Merchant
    $router->get('apieksternal/merchant/login', 'APIEksternal\HijrahMerchantController@loginForAuth');
    $router->get('apieksternal/merchant/refresh', 'APIEksternal\HijrahMerchantController@refreshAuth');
    $router->post('apieksternal/merchant/masjid', 'APIEksternal\HijrahMerchantController@masjid');
    $router->get('apieksternal/merchant/masjid/home', 'APIEksternal\HijrahMerchantController@homeMasjid');
    $router->post('apieksternal/merchant/masjid/detail', 'APIEksternal\HijrahMerchantController@detailMasjid');
    $router->get('apieksternal/merchant/listBank', 'APIEksternal\HijrahMerchantController@listBankPurwantara');
    $router->get('apieksternal/merchant/VA/{id}', 'APIEksternal\HijrahMerchantController@createdVa');
    $router->post('apieksternal/merchant/callback', 'APIEksternal\HijrahMerchantController@callbackMerchant');
    $router->get('apieksternal/merchant/akun/{id}', 'APIEksternal\HijrahMerchantController@akunBankMasjid');

    //Purwantara Controller
    $router->post('apieksternal/purwantara/callback', 'APIEksternal\PurwantaraController@callbackPurwantara');
    $router->post('apieksternal/purwantara/listChannel', 'APIEksternal\PurwantaraController@listChannel');
    $router->post('apieksternal/purwantara/va', 'APIEksternal\PurwantaraController@purwantaraVA');
    $router->post('apieksternal/purwantara/qris', 'APIEksternal\PurwantaraController@purwantaraQrisShoope');
    $router->post('apieksternal/purwantara/ewallet', 'APIEksternal\PurwantaraController@purwantaraEwalletOvo');
    //DOKU for BSI 
    $router->get('apieksternal/doku/va', 'APIEksternal\DokuController@createdVA');
    $router->post('apieksternal/doku/callback', 'APIEksternal\DokuController@callbackDokuVA');

    //Merchant Transaction
    $router->post('merchant/transaction', 'MerchantTransactionController@insertTransactionMerchant');
    $router->post('merchant/transaction/va', 'MerchantTransactionController@insertTransactionMerchantForVA');
    $router->post('merchant/transaction/doku/va', 'MerchantTransactionController@insertTransactionDokuVA');
    $router->get('merchant/transaction/{id}', 'MerchantTransactionController@detailDonasi');

    //BankCodes
    $router->get('bankCode', 'BankCodeController@getBankCode');
    $router->post('bankCode/search', 'BankCodeController@searchBankCode');
    $router->post('bankCode', 'BankCodeController@insert');
    $router->post('bankCode/{id}', 'BankCodeController@update');
    $router->delete('bankCode/{id}', 'BankCodeController@destroy');
    $router->get('bankSps', 'BankCodeController@getBankSps');
    

    //Transfer Transaksi
    $router->get('codeUnik', 'TransferTransactionsController@cekCodeUnik');
    $router->get('biayaAdmin', 'TransferTransactionsController@cekBiayaAdmin');
    $router->get('spsBank', 'TransferTransactionsController@cekSpsBank');
    $router->post('transferRecipient', 'TransferTransactionsController@insertPenerima');
    $router->put('transferNominal/{id}', 'TransferTransactionsController@insertNominal');
    $router->put('transferTotal/{id}', 'TransferTransactionsController@insertAmount');
    $router->get('detailTransfer/{id}', 'TransferTransactionsController@getDetailTransfer');
    $router->put('bankSps/{id}', 'TransferTransactionsController@updateSpsBank');

    //Topup PRA / PASCA bayar
    $router->post('topupPraBayar', 'TransactionTopupController@insertPraBayar');
    $router->post('topupPascaBayar', 'TransactionTopupController@insertPascaBayar');
    $router->post('topupPraBayarEwallet', 'TransactionTopupController@insertPraBayarQris');
    $router->post('topupPascaBayarEwallet', 'TransactionTopupController@insertPascaBayarQris');
    $router->get('transactionTopup/{id}', 'TransactionTopupController@getTransactionTopup');

    //logtransaksi
    $router->get('paymentHistory/{id}', 'LogTransactionController@hitoryTransaction');
    $router->post('paymentHistory/search', 'LogTransactionController@searchHitoryTransaction');

    //ATM OY
    $router->post('lokasiAtm', 'AtmLocationController@lokasi');
    $router->post('lokasiImage', 'AtmLocationController@getImage');

    // Setor Tarik
    $router->post('payment/setor', 'CashTransactionController@insertSetor');
    $router->post('payment/tarik', 'CashTransactionController@insertTarik');
    $router->get('payment/setortarik/{id}', 'CashTransactionController@detaiSetorTarik');
    $router->get('tester', 'CashTransactionController@tester');

    //HELP CENTER
    $router->get('helpCenter', 'HelpCenterController@listHelpCenter');
    $router->post('helpCenter', 'HelpCenterController@insert');
    $router->put('helpCenter/{id}', 'HelpCenterController@update');
    $router->delete('helpCenter/{id}', 'HelpCenterController@destroy');

    //CUSTOMER COMPLAIN
    $router->post('complain/user', 'CustomerComplainController@listComplainByUser');
    $router->post('complain', 'CustomerComplainController@insert');


    $router->get('complain', 'CustomerComplainController@listComplain');
    $router->get('complain/type', 'MenuHomeController@getMenuHomeForComplain');

    //IKI
    $router->get('iki/allCategory','APIEksternal\IKIController@getAllCategory');
    $router->get('iki/allPackageProduk','APIEksternal\IKIController@getAllPackageProduk');
    $router->get('iki/allPackageProdukbyCategory','APIEksternal\IKIController@getAllPackageProdukbyCategory');
    $router->get('iki/allPackageProdukbyEdr','APIEksternal\IKIController@getAllPackageProdukbyEdr');
    $router->post('iki/ppob/process','APIEksternal\IKIController@processPPOB');
    $router->post('iki/ppob/continuePayment','APIEksternal\IKIController@continuePaymentPPOB');
    $router->post('iki/payloadCode','APIEksternal\IKIController@getPayloadCode');

    //Website Hijrah Nuswantara
    $router->get('articles/all', 'ArtikelController@getAllDataArtikel');
    $router->get('article/{id}', 'ArtikelController@getDetilArtikel');

    //End API Mobile//



    //Start Backoffice//

    //Auth Back Office
    $router->post('backoffice/login', 'Auth\BackOffice\LoginController@login');
    $router->post('backoffice/logout', 'Auth\BackOffice\LoginController@logout');
    $router->post('backoffice/register', 'Auth\BackOffice\RegisterController@saveRegister');
    $router->post('backoffice/status', 'Auth\BackOffice\LoginController@cekToken');

    //User back Office
    $router->get('backoffice/users/first/{take}', 'BackOffice\UserController@getDataUserFirstPage');
    $router->get('backoffice/users/next/{take}/page/{page}', 'BackOffice\UserController@getDataUserByPage');
    $router->put('backoffice/user/{id}', 'BackOffice\UserController@updateUser');
    $router->delete('backoffice/user/{id}', 'BackOffice\UserController@deleteUser');
    $router->post('backoffice/users/by', 'BackOffice\UserController@getDataUserBySearch');
    $router->put('backoffice/pass/{id}', 'BackOffice\UserController@changePassword');

    //Role User back Office
    $router->get('backoffice/roles', 'BackOffice\RoleController@getDataRole');
    $router->get('backoffice/roles/first/{take}', 'BackOffice\RoleController@getDataRoleFirstPage');
    $router->get('backoffice/roles/next/{take}/page/{page}', 'BackOffice\RoleController@getDataRoleByPage');
    $router->post('backoffice/role', 'BackOffice\RoleController@saveRole');
    $router->put('backoffice/role/{id}', 'BackOffice\RoleController@updateRole');
    $router->delete('backoffice/role/{id}', 'BackOffice\RoleController@deleteRole');
    $router->post('backoffice/roles/by', 'BackOffice\RoleController@getDataRoleBySearch');

    //Menu back Office
    $router->get('backoffice/menus', 'BackOffice\RoleController@listMenus');

    //Payment back office
    $router->get('backoffice/payment/first/{take}', 'BackOffice\PaymentController@listPaymentFirstPage');
    $router->get('backoffice/payment/next/{take}/page/{page}', 'BackOffice\PaymentController@listPaymentByPage');
    $router->put('backoffice/approvedPayment', 'BackOffice\PaymentController@approvedPayment');
    $router->put('backoffice/rejectedPayment', 'BackOffice\PaymentController@rejectedPayment');
    $router->post('backoffice/payment/by', 'BackOffice\PaymentController@searchPayment');
    $router->put('backoffice/adjustments', 'BackOffice\PaymentController@pembayaran');
    $router->post('backoffice/apieksternal/pergiumroh/umrohRelease', 'APIEksternal\PergiUmrohController@umrohRelease');

    //Artikel Backoffice
    $router->post('backoffice/article', 'ArtikelController@insert');
    $router->post('backoffice/articles/by', 'ArtikelController@getDataArtikelBySearch');
    $router->post('backoffice/article/{id}', 'ArtikelController@update');
    $router->delete('backoffice/article/{id}', 'ArtikelController@destroy');
    $router->get('backoffice/articles/first/{take}', 'ArtikelController@getDataArtikelFirstPage');
    $router->get('backoffice/articles/next/{take}/page/{page}', 'ArtikelController@getDataArtikelByPage');

    //Inspirasi Backoffice
    $router->post('backoffice/inspiration', 'InspirationController@insert');
    $router->post('backoffice/inspiration/by', 'InspirationController@getDataInspirationBySearch');
    $router->post('backoffice/inspiration/{id}', 'InspirationController@update');
    $router->delete('backoffice/inspiration/{id}', 'InspirationController@destroy');
    $router->get('backoffice/inspiration/first/{take}', 'InspirationController@getDataInspirationFirstPage');
    $router->get('backoffice/inspiration/next/{take}/page/{page}', 'InspirationController@getDataInspirationByPage');

    //Kategori Backoffice
    $router->post('backoffice/category', 'CategoryController@insert');
    $router->get('backoffice/categories/all', 'CategoryController@getDataCategory');
    $router->post('backoffice/categories/by', 'CategoryController@getDataCategoryBySearch');
    $router->put('backoffice/category/{id}', 'CategoryController@update');
    $router->delete('backoffice/category/{id}', 'CategoryController@destroy');
    $router->get('backoffice/categories/first/{take}', 'CategoryController@getDataCategoryFirstPage');
    $router->get('backoffice/categories/next/{take}/page/{page}', 'CategoryController@getDataCategoryByPage');

    //MenuHome Backoffice
    $router->post('backoffice/menuhome', 'MenuHomeController@insert');
    $router->post('backoffice/menuhome/by', 'MenuHomeController@getDataMenuMobileBySearch');
    $router->post('backoffice/menuhome/{id}', 'MenuHomeController@update');
    $router->delete('backoffice/menuhome/{id}', 'MenuHomeController@destroy');
    $router->get('backoffice/menuhome/first/{take}', 'MenuHomeController@getDataMenuMobileFirstPage');
    $router->get('backoffice/menuhome/next/{take}/page/{page}', 'MenuHomeController@getDataMenuMobileByPage');

    //Penceramah Backoffice
    $router->post('backoffice/lecturer', 'LecturerController@insert');
    $router->post('backoffice/lecturer/by', 'LecturerController@getDataLecturerBySearch');
    $router->post('backoffice/lecturer/{id}', 'LecturerController@update');
    $router->delete('backoffice/lecturer/{id}', 'LecturerController@destroy');
    $router->get('backoffice/lecturer/first/{take}', 'LecturerController@getDataLecturerFirstPage');
    $router->get('backoffice/lecturer/next/{take}/page/{page}', 'LecturerController@getDataLecturerByPage');

    //Quran Backofiice
    $router->get('backoffice/quran/first/{take}', 'QuranController@getDataQuranFirstPage');
    $router->get('backoffice/quran/next/{take}/page/{page}', 'QuranController@getDataQuranByPage');
    $router->get('backoffice/quran/by', 'QuranController@getDataQuranBySearch');
    // $router->post('backoffice/quran/file/{id}','QuranController@uploadAyat');
    $router->put('backoffice/quran/{id}', 'QuranController@updateSurah');
    $router->put('backoffice/quran/ayat/{id}', 'QuranController@updateAyat');
    // $router->put('backoffice/quran/ayat/add', 'QuranController@updateQuran');

    //Term & Conditions Backoffice
    $router->post('backoffice/termconditions', 'TermConditionController@save');
    $router->put('backoffice/termconditions/{id}', 'TermConditionController@update');
    $router->delete('backoffice/termconditions/{id}', 'TermConditionController@destroy');
    $router->get('backoffice/termconditions/first/{take}', 'TermConditionController@getDataTermFirstPage');
    $router->get('backoffice/termconditions/next/{take}/page/{page}', 'TermConditionController@getDataTermByPage');

    //PrivacyPolicies Backoffice
    $router->post('backoffice/privacyPolicies', 'PrivacyPoliciesController@insert');
    $router->put('backoffice/privacyPolicies/{id}', 'PrivacyPoliciesController@update');
    $router->delete('backoffice/privacyPolicies/{id}', 'PrivacyPoliciesController@destroy');
    $router->get('backoffice/privacyPolicies/first/{take}', 'PrivacyPoliciesController@getDataPrivacyFirstPage');
    $router->get('backoffice/privacyPolicies/next/{take}/page/{page}', 'PrivacyPoliciesController@getDataPrivacyByPage');

    //Doa Backoffice
    $router->post('backoffice/doa/by', 'DoaController@getDataDoaBySearch');
    $router->post('backoffice/doa', 'DoaController@insert');
    $router->put('backoffice/doa/{id}', 'DoaController@update');
    $router->delete('backoffice/doa/{id}', 'DoaController@destroy');
    $router->get('backoffice/doa/first/{take}', 'DoaController@getDataDoaFirstPage');
    $router->get('backoffice/doa/next/{take}/page/{page}', 'DoaController@getDataDoaByPage');

    //FCM Backoffice
    $router->post('backoffice/apieksternal/fcm/message/artikel', 'APIEksternal\FCMController@sendMessageArtikel');
    $router->post('backoffice/apieksternal/fcm/message/inspirasi', 'APIEksternal\FCMController@sendMessageInspirasi');

    //End Backoffice//

});
