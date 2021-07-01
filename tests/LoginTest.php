<?php

namespace Tests;

use Tests\TestCase;

class LoginTest extends TestCase
{
    
    public function test_cek_hp_terdaftar()
    {
        $data = [
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/login',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'101']);

    }

    public function test_validasi_pin()
    {
        $data = [
            "pinUser" => "123456",
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/pin/validasi',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_cek_hp_blm_terdaftar()
    {
        $data = [
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/login',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'106']);

    }

    public function test_save_telp()
    {
        $data = [
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/telp',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);
        $this->seeInDatabase('usermobiles', ['noTelpUser'=>'085691116373']);

    }

    public function test_login_validasi_otp()
    {
        $data = [
            "kodeOTP" => "3497",
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/otp/validasi',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_login_resend_otp()
    {
        $data = [
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/reSend',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_login_save_pin()
    {
        $data = [
            "pinUser" => "123456",
            "noTelpUser" => "085691116373"
        ];

        $this->json('PUT','apihijrah/v2/pin',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);
        $this->seeInDatabase('usermobiles', ['pinUser' => '123456']);

    }
}
