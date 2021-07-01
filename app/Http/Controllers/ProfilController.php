<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\UserMobiles;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\CheckValidation;
use Illuminate\Support\Str;
use App\Events\CacheFlushEvent;
use App\Http\Controllers\APIEksternal\IlumaController;

class ProfilController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLogin');
        $this->middleware('onlyJson', ['only' => ['updateNameProfile', 'updateEmailProfile', 'updateNoTelpProfile', 'updatePinProfile']]);
    }

    public function getDataUserById($id)
    {
        try {
            $newPolicies = new PrivacyPoliciesController;
            $newTerm = new TermConditionController;

            $key = Str::of(Cache::get('key', 'users:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $resultAll = Cache::remember('users:' . $id . date('Y-m-d'), env('CACHE_DURATION'), function () use ($newPolicies, $newTerm, $id) {
                $resultUser = UserMobiles::where('_id', $id)->first();
                $resultPolicies = $newPolicies->view();
                $resultTerm = $newTerm->getData();

                return response()->json([
                    'statusCode' => '000',
                    'message' => 'Sukses',
                    'isEditProfile' => $resultUser->flag,
                    'UserMobile' => $resultUser,
                    'PrivecyPolicies' => $resultPolicies,
                    'TermConditions' => $resultTerm,
                ]);
            });
            if ($resultAll) {
                $response = $resultAll;
            } else {
                $response = response()->json(['statusCode' => '723', 'message' => 'Error Get data Profile']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    // input nama
    public function updateNameProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));
        $data = UserMobiles::where('_id', $id)->first();
        try {
            $data->namaUser = $req->input('namaUser');
            if ($data->save()) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            } else {
                return response()->json(['statusCode' => '723', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateEmailProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));

        $data = UserMobiles::where('_id', $id)->first();
        $validEmail = new CheckValidation;

        $validatorEmail = Validator::make($req->all(), UserMobiles::$rulesEmail, UserMobiles::$messages);
        try {
            if ($validatorEmail->fails()) {
                return response()->json(['statusCode' => '223', 'message' => implode(" ", $validatorEmail->messages()->all())]);
            } else if (json_decode($validEmail->cekValidasiDaftarEmail($req->emailUser))->statusCode == '224') {
                return $validEmail->cekValidasiDaftarEmail($req->emailUser);
            } else if ($req->emailUser != null) {
                $data->emailUser        = $req->input('emailUser');
                if ($data->save()) {
                    return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
                } else {
                    return response()->json(['statusCode' => '723', 'message' => 'Gagal']);
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateNoTelpProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));

        $noTelp = $req->noTelpUser;

        $validatorPanjang = Validator::make($req->all(), UserMobiles::$rulesNoTelpMin, UserMobiles::$messages);
        $validatorAngka = Validator::make($req->all(), UserMobiles::$rulesNoTelpNumeric, UserMobiles::$messages);
        $checkTelp = new CheckValidation;
        try {
            if ($validatorPanjang->fails()) {
                return response()->json(['statusCode' => '222', 'message' => implode(" ", $validatorPanjang->messages()->all())]);
            } else if ($validatorAngka->fails()) {
                return response()->json(['statusCode' => '322', 'message' => implode(" ", $validatorAngka->messages()->all())]);
            } else {

                if (json_decode($checkTelp->cekValidasiNoTelp($noTelp))->statusCode == '106') {
                    $data       = UserMobiles::where('_id', $id)->first();
                    $otp_send   = new LoginController;

                    $data->noTelpUser = $noTelp;

                    if ($data->save()) {
                        $otp = json_decode($otp_send->updateOtp($req));
                        if ($otp->statusCode == '000') {
                            return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data, 'statusOTP' => $otp->statusOTP));
                        } else {
                            return $otp_send->updateOtp($req);
                        }
                    } else {
                        return json_encode(array('statusCode' => '107', 'message' => "Gagal Simpan No Telp"));
                    }
                } else {
                    return $checkTelp->cekValidasiNoTelp($noTelp);
                }
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateFotoProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));

        $validatorUrlFoto = Validator::make($req->all(), UserMobiles::$rulesurlFoto, UserMobiles::$messages);
        $validatorFormat  = Validator::make($req->all(), UserMobiles::$rulesFormaturlFoto, UserMobiles::$messages);
        $validatorMax     = Validator::make($req->all(), UserMobiles::$rulesMaxurlFoto, UserMobiles::$messages);

        if ($validatorUrlFoto->fails()) {
            $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
        } else if ($validatorFormat->fails()) {
            $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
        } else if ($validatorMax->fails()) {
            $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
        } else {

            $data       = UserMobiles::where('_id', $id)->first();
            if ($data->urlFoto != "") {
                $ex = explode("/", $data->urlFoto);
                Storage::disk('oss')->delete('/foto_profil/' . $ex[4]);
            }

            if ($req->hasFile('urlFoto')) {

                $files = $req->file('urlFoto'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/foto_profil/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }
            $data->urlFoto = env('OSS_DOMAIN') . $filePath;

            if ($data->save()) {
                return $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                return $response = json_encode(array('statusCode' => '849', 'message' => "Gagal Simpan Foto Profile"));
            }
        }

        return $response;
    }

    public function updatePinProfile(Request $req, $id)
    {
        $validate = Validator::make($req->all(), [
            'pinUserBaru'         => 'required|min:6',
        ], [
            'pinUserBaru.required'  => 'Pin User Baru Tidak Boleh Kosong',
            'pinUserBaru.min'       => 'Pin User hanya 6 Digit',
        ]);
        if ($validate->fails()) {
            return response()->json(['statusCode' => '679', 'message' => implode(" ", $validate->messages()->all())]);
        }
        Cache::forget('users:' . $id . date('Y-m-d'));

        try {

            $pin = UserMobiles::where(['pinUser' => $req->pinUser, '_id' => $id])->count();
            if ($pin > 0) {
                $data = UserMobiles::where(['pinUser' => $req->pinUser, '_id' => $id])->first();
                $data->pinUser = $req->pinUserBaru;

                if ($data->save()) {
                    return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
                } else {
                    return json_encode(array('statusCode' => '829', 'message' => "Gagal Simpan Pin Baru"));
                }
            } else {
                return response()->json(['statusCode' => '671', 'message' => 'Pin Lama Salah']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    // UNTUK VERIFIKASI AKUN DI V3
    public function updateDataProfile(Request $req, $id)
    {
        $validEmail = new CheckValidation;

        if (!empty($validEmail->validateEmptyEmail($req))) {
            return $validEmail->validateEmptyEmail($req);
        }
        Cache::forget('users:' . $id . date('Y-m-d'));

        try {

            $data = UserMobiles::where('_id', $id)->first();
            $data->emailUser        = $req->emailUser;
            $data->statusVerifikasi = "2";

            if ($data->save()) {
                return json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                return json_encode(array('statusCode' => '829', 'message' => "Gagal Simpan Pin Baru"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateNikProfile(Request $req, $id)
    {
        $validatorMax   = Validator::make($req->all(), UserMobiles::$rulesNikNumeric, UserMobiles::$messages);
        $validatorDigit = Validator::make($req->all(), UserMobiles::$rulesNikDigit, UserMobiles::$messages);
        $validNama      = new CheckValidation;

        if (!empty($validNama->validateEmptyNama($req))) {
            return $validNama->validateEmptyNama($req);
        }
        if ($validatorMax->fails()) {
           return response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorMax->messages()->all())]);
        }
        if ($validatorDigit->fails()) {
           return response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorDigit->messages()->all())]);
        }
        Cache::forget('users:' . $id . date('Y-m-d'));

        $newIluma    = new IlumaController;
        
        try {

            $data = UserMobiles::where('_id', $id)->first();
            $data->nik = $req->nik;
            $data->namaUser = $req->namaUser;

            if ($data->save()) {
                return $newIluma->cekKTP($req);
            } else {
                return json_encode(array('statusCode' => '850', 'message' => "Gagal Update Status Nik"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            return json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
    }

    public function updateFotoKtpProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));

        $validatorUrlFoto = Validator::make($req->all(), UserMobiles::$rulesurlFotoKtp, UserMobiles::$messages);
        $validatorFormat  = Validator::make($req->all(), UserMobiles::$rulesFormatFotoKtp, UserMobiles::$messages);
        $validatorMax     = Validator::make($req->all(), UserMobiles::$rulesMaxFotoKtp, UserMobiles::$messages);

        if ($validatorUrlFoto->fails()) {
            $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
        } else if ($validatorFormat->fails()) {
            $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
        } else if ($validatorMax->fails()) {
            $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
        } else {

            $data       = UserMobiles::where('_id', $id)->first();
            if ($data->urlFotoKtp != "") {
                $ex = explode("/", $data->urlFotoKtp);
                Storage::disk('oss')->delete('/foto_ktp/' . $ex[4]);
            }

            if ($req->hasFile('urlFotoKtp')) {

                $files = $req->file('urlFotoKtp'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/foto_ktp/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }
            $data->urlFotoKtp = env('OSS_DOMAIN') . $filePath;

            if ($data->save()) {
                return $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                return $response = json_encode(array('statusCode' => '849', 'message' => "Gagal Simpan Foto Profile"));
            }
        }

        return $response;
    }

    public function updateFotoSelfieKtpProfile(Request $req, $id)
    {
        Cache::forget('users:' . $id . date('Y-m-d'));

        $validatorUrlFoto = Validator::make($req->all(), UserMobiles::$rulesurlFotoSelfieKtp, UserMobiles::$messages);
        $validatorFormat  = Validator::make($req->all(), UserMobiles::$rulesFormatFotoSelfieKtp, UserMobiles::$messages);
        $validatorMax     = Validator::make($req->all(), UserMobiles::$rulesMaxFotoSelfieKtp, UserMobiles::$messages);

        if ($validatorUrlFoto->fails()) {
            $response = response()->json(['statusCode' => '679', 'message' => implode(" ", $validatorUrlFoto->messages()->all())]);
        } else if ($validatorFormat->fails()) {
            $response = response()->json(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
        } else if ($validatorMax->fails()) {
            $response = response()->json(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
        } else {

            $data       = UserMobiles::where('_id', $id)->first();
            if ($data->urlFotoSelfieKtp != "") {
                $ex = explode("/", $data->urlFotoSelfieKtp);
                Storage::disk('oss')->delete('/foto_selfie_ktp/' . $ex[4]);
            }

            if ($req->hasFile('urlFotoSelfieKtp')) {

                $files = $req->file('urlFotoSelfieKtp'); // will get all files
                $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                $filePath = '/foto_selfie_ktp/' . $file_name;
                if (Storage::disk('oss')->exists($filePath)) {
                    Storage::disk('oss')->delete($filePath);
                }

                Storage::disk('oss')->put($filePath, file_get_contents($files));
            }
            $data->urlFotoSelfieKtp = env('OSS_DOMAIN') . $filePath;
            $data->statusVerifikasi = "3";
            $data->flag             = true;

            if ($data->save()) {
                return $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'data' => $data));
            } else {
                return $response = json_encode(array('statusCode' => '849', 'message' => "Gagal Simpan Foto Profile"));
            }
        }
        return $response;
    }
}
