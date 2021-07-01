<?php

namespace App\Http\Controllers\APIEksternal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GatewayController extends Controller
{
    public function smsSend($noTelp, $code)
    {
        try{
            $client = new Client();
            // $hari_array = array(
            //     'Minggu',
            //     'Senin',
            //     'Selasa',
            //     'Rabu',
            //     'Kamis',
            //     'Jumat',
            //     'Sabtu'
            // );
            // $hr = date('w');
            // $hari = $hari_array[$hr];
            // $tanggal = date('j');
            // $bulan_array = array(
            //     1 => 'Januari',
            //     2 => 'Februari',
            //     3 => 'Maret',
            //     4 => 'April',
            //     5 => 'Mei',
            //     6 => 'Juni',
            //     7 => 'Juli',
            //     8 => 'Agustus',
            //     9 => 'September',
            //     10 => 'Oktober',
            //     11 => 'November',
            //     12 => 'Desember',
            // );
            // $bl = date('n');
            // $bulan = $bulan_array[$bl];
            // $tahun = date('Y');
            $jam = date('H:i:s');
            // $year = ($tahun < 1000) ? $tahun + 1900 : $tahun;
            $hash = (String) hash('sha256', env('USERNAME_GATEWAY').env('PASSWORD_GATEWAY').$jam);
            $RandNum5  = mt_rand(100000000000,999999999999);
            // .$hari.', '. $tanggal.' '. $bulan.' '. $tahun.' '.$jam.' - '. env('USERNAME_GATEWAY').' - '
            $arr = [
                'msisdn' => $noTelp,
                'message' => 'Kode OTP '.$code.'. Kode ini rahasia & tidak boleh dibagikan ke siapapun. Hijrah',
                "backup_on" => "",
                "backup_exp" => ""
            ];
            $result = [
                "signature"=> $hash,
                "time" => $jam,
                "channel" => [
                    'sms' => $arr
                ],
                "type" => "2",
                "username" => env('USERNAME_GATEWAY'),
                "ref_id" => "TesProd".env('USERNAME_GATEWAY').$RandNum5,
                "subject" => "Testing prod ".env('USERNAME_GATEWAY'),
                "sender_id" => env('SENDER_ID'),
                "testing" => null
            ];
            
            $data_send = json_encode($result);
            $data = $client->post(env('API_GATEWAY'),[
                'body' => $data_send
            ])->getBody()->getContents();
            $result_data    = json_decode($data);
            $response       = $result_data->rc;
        }
        catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }

    public function zenzivaSms($noTelp,$code)
    {
        try {
            $client = new Client();
            $body = json_encode([
                "userkey"   => ENV("USER_KEY_ZENZIVA"),
                "passkey"   => ENV("PASSWORD_KEY_ZENZIVA"),
                "to"        => $noTelp,
                "message"   => 'Kode OTP '.$code.'. Kode ini rahasia & tidak boleh dibagikan ke siapapun. Hijrah'
            ]);
            $send = [
                'headers'   => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'      => $body
            ];
            $data = $client->post(env("API_GATEWAY_ZENZIVA")."sendsms/", $send)->getBody()->getContents();
            $result = json_decode($data);
            $response = $result->status;
        } catch(RequestException $e){
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }

        return $response;
    }
    
}