<?php

namespace Tests;

use Tests\TestCase;

class KycTest extends TestCase
{
    
    public function test_verifikasi_data()
    {
        $id = "60050ebb0d05f823f80efa45";
        $data = [
            "nik"       => "8171022711940007",
            "namaUser"  => "Muhammad Furqon Rahawarin"
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('PUT','apieksternal/cek/ktp/'.$id,$data, $header);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_verifikasi_email()
    {
        $id = "60050ebb0d05f823f80efa45";
        $data = [
            "emailUser" => "example@gmail.com"
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('PUT','profile/data/'.$id,$data,$header);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_verifikasi_ktp()
    {
        $id = "60050ebb0d05f823f80efa45";
        $data = [
            'urlFotoKtp' => UploadedFile::fake()->image('/foto_ktp/foto_ktp.jpg')
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('POST','profile/fotoKtp/'.$id,$data, $header);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }

    public function test_verifikasi_selfie_ektp()
    {
        $id = "60050ebb0d05f823f80efa45";
        $data = [
            'urlFotoSelfieKtp' => UploadedFile::fake()->image('/foto_selfie_ktp/foto_selfie_ktp.jpg')
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('POST','profile/selfieKtp/'.$id,$data, $header);
        $this->seeStatusCode(200);
        $this->seeJsonContains(['statusCode'=>'000']);

    }
}
