<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Lecturers;
use Intervention\Image\Facades\Image as Image;
use Storage;
use App\Events\CacheFlushEvent;
use App\Events\BackOfficeUserLogEvent;

class LecturerController extends Controller
{
    private $token, $emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice', ['only' => ['insert', 'update', 'destroy']]);
        $this->middleware('onlyJson',['only'=>['search','getDataLecturerBySearch']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token', $this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id', $token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function search(Request $req)
    {
        try {
            $key = Str::of(Cache::get('key', 'lecturer_search:' . date('Y-m-d') . $req->q))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('lecturer_search:' . date('Y-m-d') . $req->q, env('CACHE_DURATION'), function () use ($req) {
                return Lecturers::where('lecturerStatus', '1')->where('lecturerName', 'like', "%" . $req->q . "%")->get();
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
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

    // Menampilkan semua penceramah
    public function getLecturer()
    {
        try {
            $key = Str::of(Cache::get('key', 'lecturers:' . date('Y-m-d')))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('lecturers:' . date('Y-m-d'), env('CACHE_DURATION'), function (){
                return json_decode(Lecturers::where('lecturerStatus', '1')->orderBy('created_at', 'DESC')->get());
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
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

    // Menampilkan Detail Penceramah
    public function viewDetail($id)
    {
        try {
            $key = Str::of(Cache::get('key', 'detail_lecturers:' . date('Y-m-d') . ':' . $id))->explode(':')[1];
            event(new CacheFlushEvent($key));

            $cache = Cache::remember('detail_lecturers:' . date('Y-m-d') . ':' . $id, env('CACHE_DURATION'), function () use ($id) {
                $lecture =  Lecturers::where('_id', $id)->first();
                $arr_galery = [
                    $lecture->lecturerGallery1,
                    $lecture->lecturerGallery2,
                    $lecture->lecturerGallery3,
                    $lecture->lecturerGallery4,
                ];

                if ($arr_galery != '') {
                    for ($i = 0; $i < count($arr_galery); $i++) {
                        $arrImg[] = [
                            'image' => $arr_galery[$i],
                        ];
                    }
                }
                $arr_lect = [
                    '_id' => $lecture->_id,
                    'lecturerName' => $lecture->lecturerName,
                    'lecturerAddress' => $lecture->lecturerAddress,
                    'lecturerDesc' => $lecture->lecturerDesc,
                    'lecturerPhoto' => $lecture->lecturerPhoto,
                    'lecturerGallery' => $arrImg,
                    'lecturerDateofBirth' => $lecture->lecturerDateofBirth,
                    'lecturerTelp' => $lecture->lecturerTelp,
                    'lecturerEmail' => $lecture->lecturerEmail,
                    'lecturerAlmamater' => $lecture->lecturerAlmamater,
                    'lecturerSosmed' => $lecture->lecturerSosmed,
                    'lecturerStatus' => $lecture->lecturerStatus,
                ];
                return $arr_lect;
            });
            if ($cache) {
                return response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $cache]);
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

    public function insert(Request $req)
    {
        $validatorFoto = Validator::make($req->all(), Lecturers::$rulesFotoPenceramah, Lecturers::$messages);
        $validatorFormat = Validator::make($req->all(), Lecturers::$rulesFormatPenceramah, Lecturers::$messages);
        $validatorMax    = Validator::make($req->all(), Lecturers::$rulesMaxPenceramah, Lecturers::$messages);

        try {
            if ($req->lecturerPhoto != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($req->hasFile('lecturerPhoto')) {

                    $files = $req->file('lecturerPhoto'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/foto_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerPhoto =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery1 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($req->hasFile('lecturerGallery1')) {

                    $files = $req->file('lecturerGallery1'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery1 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery2 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($req->hasFile('lecturerGallery2')) {

                    $files = $req->file('lecturerGallery2'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery2 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery3 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($req->hasFile('lecturerGallery3')) {

                    $files = $req->file('lecturerGallery3'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery3 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery4 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                if ($req->hasFile('lecturerGallery4')) {

                    $files = $req->file('lecturerGallery4'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery4 =  env('OSS_DOMAIN') . $filePath;
                }
            }

            $data = new Lecturers;

            $data->lecturerName         = $req->lecturerName;
            $data->lecturerAddress      = $req->lecturerAddress;
            $data->lecturerDesc         = $req->lecturerDesc;
            $data->lecturerPhoto        = $req->lecturerPhoto != 'null' ? $lecturerPhoto : '';
            $data->lecturerGallery1     = $req->lecturerGallery1 != 'null' ? $lecturerGallery1 : '';
            $data->lecturerGallery2     = $req->lecturerGallery2 != 'null' ? $lecturerGallery2 : '';
            $data->lecturerGallery3     = $req->lecturerGallery3 != 'null' ? $lecturerGallery3 : '';
            $data->lecturerGallery4     = $req->lecturerGallery4 != 'null' ? $lecturerGallery4 : '';
            $data->lecturerDateofBirth  = $req->lecturerDateofBirth;
            $data->lecturerTelp         = $req->lecturerTelp;
            $data->lecturerEmail        = $req->lecturerEmail;
            $data->lecturerAlmamater    = $req->lecturerAlmamater;
            $data->lecturerSosmed       = $req->lecturerSosmed;
            $data->lecturerStatus       = $req->lecturerStatus;

            if ($data->save()) {
                Cache::forget('penceramah:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses", 'penceramah' => $data));
            } else {
                $response = json_encode(array('statusCode' => '251', 'message' => "Gagal Menyimpan Penceramah"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Penceramah', 'Add Penceramah - ' . $req->lecturerName, json_decode($response)->message));
        return $response;
    }

    public function update(Request $req, $id)
    {
        $validatorFoto = Validator::make($req->all(), Lecturers::$rulesFotoPenceramah, Lecturers::$messages);
        $validatorFormat = Validator::make($req->all(), Lecturers::$rulesFormatPenceramah, Lecturers::$messages);
        $validatorMax    = Validator::make($req->all(), Lecturers::$rulesMaxPenceramah, Lecturers::$messages);

        $lecturer = Lecturers::where('_id', $id)->first();
        try {
            $lecturer = Lecturers::where('_id', $id)->first();

            if ($req->lecturerPhoto != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($lecturer->lecturerPhoto != "") {
                    $lec = explode("/", $lecturer->lecturerPhoto);
                    Storage::disk('oss')->delete('foto_penceramah/' . $lec[4]);
                }

                //save gambar to storage
                // $width = 600; // your max width
                // $height = 600; // your max height
                $file_name = "";

                if ($req->hasFile('lecturerPhoto')) {

                    $files = $req->file('lecturerPhoto'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/foto_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerPhoto =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery1 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($lecturer->lecturerGallery1 != "") {
                    $lec = explode("/", $lecturer->lecturerGallery1);
                    Storage::disk('oss')->delete('gallery_penceramah/' . $lec[4]);
                }

                //save gambar to storage
                // $width = 600; // your max width
                // $height = 600; // your max height
                $file_name = "";

                if ($req->hasFile('lecturerGallery1')) {

                    $files = $req->file('lecturerGallery1'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery1 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery2 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($lecturer->lecturerGallery2 != "") {
                    $lec = explode("/", $lecturer->lecturerGallery2);
                    Storage::disk('oss')->delete('gallery_penceramah/' . $lec[4]);
                }

                //save gambar to storage
                // $width = 600; // your max width
                // $height = 600; // your max height
                $file_name = "";

                if ($req->hasFile('lecturerGallery2')) {

                    $files = $req->file('lecturerGallery2'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery2 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery3 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($lecturer->lecturerGallery3 != "") {
                    $lec = explode("/", $lecturer->lecturerGallery3);
                    Storage::disk('oss')->delete('gallery_penceramah/' . $lec[4]);
                }

                //save gambar to storage
                // $width = 600; // your max width
                // $height = 600; // your max height
                $file_name = "";

                if ($req->hasFile('lecturerGallery3')) {

                    $files = $req->file('lecturerGallery3'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery3 =  env('OSS_DOMAIN') . $filePath;
                }
            }
            if ($req->lecturerGallery4 != 'null') {
                if ($validatorFoto->fails()) {
                    $response = json_encode(['statusCode' => '679', 'message' => implode(" ", $validatorFoto->messages()->all())]);
                } else if ($validatorFormat->fails()) {
                    $response = json_encode(['statusCode' => '678', 'message' => implode(" ", $validatorFormat->messages()->all())]);
                } else if ($validatorMax->fails()) {
                    $response = json_encode(['statusCode' => '677', 'message' => implode(" ", $validatorMax->messages()->all())]);
                }

                //delete image for storage
                if ($lecturer->lecturerGallery4 != "") {
                    $lec = explode("/", $lecturer->lecturerGallery4);
                    Storage::disk('oss')->delete('gallery_penceramah/' . $lec[4]);
                }

                //save gambar to storage
                // $width = 600; // your max width
                // $height = 600; // your max height
                $file_name = "";

                if ($req->hasFile('lecturerGallery4')) {

                    $files = $req->file('lecturerGallery4'); // will get all files
                    $file_name = time() . '.' . $files->getClientOriginalName(); //Get file original name

                    $filePath = '/gallery_penceramah/' . $file_name;
                    if (Storage::disk('oss')->exists($filePath)) {
                        Storage::disk('oss')->delete($filePath);
                    }

                    Storage::disk('oss')->put($filePath, file_get_contents($files));
                    $lecturerGallery4 =  env('OSS_DOMAIN') . $filePath;
                }
            }

            $data = array(
                'lecturerName'        => $req->lecturerName,
                'lecturerAddress'     => $req->lecturerAddress,
                'lecturerDesc'        => $req->lecturerDesc,
                'lecturerPhoto'       => $req->lecturerPhoto != 'null' ? $lecturerPhoto : $lecturer->lecturerPhoto,
                'lecturerGallery1'    => $req->lecturerGallery1 != 'null' ? $lecturerGallery1 : $lecturer->lecturerGallery1,
                'lecturerGallery2'    => $req->lecturerGallery2 != 'null' ? $lecturerGallery2 : $lecturer->lecturerGallery2,
                'lecturerGallery3'    => $req->lecturerGallery3 != 'null' ? $lecturerGallery3 : $lecturer->lecturerGallery3,
                'lecturerGallery4'    => $req->lecturerGallery4 != 'null' ? $lecturerGallery4 : $lecturer->lecturerGallery4,
                'lecturerDateofBirth' => $req->lecturerDateofBirth,
                'lecturerTelp'        => $req->lecturerTelp,
                'lecturerEmail'       => $req->lecturerEmail,
                'lecturerAlmamater'   => $req->lecturerAlmamater,
                'lecturerSosmed'      => $req->lecturerSosmed,
                'lecturerStatus'      => $req->lecturerStatus,
            );

            if ($lecturer->update($data)) {
                Cache::forget('penceramah:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '238', 'message' => "Gagal Update Penceramah"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Penceramah', 'Update Penceramah - ' . $req->lecturerName, json_decode($response)->message));
        return $response;
    }

    public function destroy($id)
    {
        try {
            $lecturer = Lecturers::where('_id', $id)->first();
            $namaPenceramah = $lecturer->lecturerName;

            //delete image for storage
            $data = $lecturer->lecturerPhoto != '' ? explode("/", $lecturer->lecturerPhoto) : '';
            $data != '' ? Storage::disk('oss')->delete('foto_penceramah/' . $data[4]) : '';

            if ($lecturer->delete()) {
                Cache::forget('penceramah:' . date('Y-m-d'));
                $response = json_encode(array('statusCode' => '000', 'message' => "Sukses"));
            } else {
                $response = json_encode(array('statusCode' => '620', 'message' => "Gagal Hapus Artikel"));
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin, 'Penceramah', 'Delete Penceramah - ' . $namaPenceramah, json_decode($response)->message));
        return $response;
    }

    public function getDataLecturerFirstPage($take)
    {
        try {
            $results = Lecturers::skip(0)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = Lecturers::count();
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

    public function getDataLecturerByPage($take, $page)
    {
        $skip = ($take * $page) - $take;
        try {
            $results = Lecturers::skip($skip)->take((int)$take)->orderBy('_id', 'desc')->get();
            $totalData = Lecturers::count();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results, 'total' => $totalData]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataLecturerBySearch(Request $req)
    {
        $val = str_replace(' ', '', $req->search);
        try {
            $results = Lecturers::where('lecturerName', 'like', '%' . $val . '%')->orderBy('_id', 'desc')->get();
            if ($results) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $results]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
