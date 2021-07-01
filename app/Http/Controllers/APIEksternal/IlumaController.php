<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class IlumaController extends Controller
{

    public function __construct()
    {
        $this->middleware('authLogin', ['only' => ['cekNPWP', 'cekKTP', 'cekBank', 'getToken']]);
        $this->middleware('onlyJson', ['only' => ['cekNPWP', 'cekKTP', 'cekBank']]);
    }

    public function cekNPWP(Request $req)
    {

        try {
            $params = json_encode([
                "account_number" => $req->noNPWP,
            ]);

            $headers = [
                "Content-Type" => "application/json",
            ];

            $client = new Client();
            $response = $client->post(
                env('URL_ILUMA') . '/v0/identity/npwp_data_requests',
                [
                    'auth' => [env('API_KEY_ILUMA'), ''],
                    'headers' => $headers,
                    'body' => $params
                ]
            )->getBody()->getContents();
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function cekKTP(Request $req)
    {

        try {
            $params = json_encode([
                "nik" => $req->nik,
                "name" => $req->namaUser
            ]);

            $headers = [
                "Content-Type" => "application/json",
            ];

            $client = new Client();
            $result = $client->post(
                env('URL_ILUMA') . '/v2/identity/ktp_data_requests',
                [
                    'auth' => [env('API_KEY_ILUMA'), ''],
                    'headers' => $headers,
                    'body' => $params
                ]
            )->getBody()->getContents();
            $data = json_decode($result);
            if ($data->status == "FOUND" && $data->name_match == true) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses', 'data' => $data]);
            } else if ($data->status == "FOUND" && $data->name_match == false) {
                $response = json_encode(['statusCode' => '300', 'message' => 'Nama dan NIK tidak sesuai harap periksa kembali', 'data' => $data]);
            } else {
                $response = json_encode(['statusCode' => '999', 'message' => 'NIK tidak sesuai harap periksa kembali NIK anda']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function cekBank(Request $req)
    {

        try {
            $params = json_encode([
                "bank_account_number" => $req->accountBank,
                "bank_code" => $req->bankCode,
                "given_name" => $req->name,
                "surname" => $req->surname
            ]);

            $headers = [
                "Content-Type" => "application/json",
            ];

            $client = new Client();
            $response = $client->post(
                env('URL_ILUMA') . '/v2/identity/bank_account_data_requests',
                [
                    'auth' => [env('API_KEY_ILUMA'), ''],
                    'headers' => $headers,
                    'body' => $params
                ]
            )->getBody()->getContents();
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function callBackNpwp(Request $req)
    {

        try {

            $token = $req->header('X-CALLBACK-TOKEN');

            if ($token == env('TOKEN_CALLBACK_ILUMA')) {

                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses Callback Cek NPWP', 'data' => $req->all()]);
            } else {
                $response = json_encode(['statusCode' => '561', 'message' => 'Gagal Callback Cek NPWP']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function callBackBank(Request $req)
    {

        try {

            $token = $req->header('X-CALLBACK-TOKEN');

            if ($token == env('TOKEN_CALLBACK_ILUMA')) {

                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses Callback Cek Bank', 'data' => $req->all()]);
            } else {
                $response = json_encode(['statusCode' => '561', 'message' => 'Gagal Callback Cek Bank']);
            }
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getToken(Request $req)
    {

        try {

            $client = new Client();
            $response = $client->get(
                env('URL_ILUMA') . '/v1/callback/authentication_tokens',
                [
                    'auth' => [env('API_KEY_ILUMA'), '']
                ]
            )->getBody()->getContents();
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
