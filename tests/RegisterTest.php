<?php

namespace Tests;

use Tests\TestCase;

class RegisterTest extends TestCase
{

    public function test_register()
    {
        $data = [
            'noTelpUser'=>'085691116373',
            'emailUser'=>'test@tes.com',
            'namaUser'=>'eri',
            'imei'=>'08yyyyy666',
            'flag'=>true
        ];

        $this->json('POST','apihijrah/v2/register',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);
        $this->seeInDatabase('usermobiles', ['noTelpUser'=>'085691116373']);

    }

    public function test_reg_validasi_otp()
    {
        $data = [
            "kodeOTP" => "4839",
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/otp/validasi',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_reg_resend_otp()
    {
        $data = [
            "noTelpUser" => "085691116373"
        ];

        $this->json('POST','apihijrah/v2/reSend',$data);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_reg_save_pin()
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
