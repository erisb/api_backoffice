<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ProfilTest extends TestCase
{

    public function test_data_profil()
    {
        $id = '60013ee3d04a000036000133';
        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('GET','apihijrah/v2/profile/'.$id,[],$header);
        $this->seeStatusCode(200);
        $this->seeJsonContains([
            'statusCode'=>'000',
            'noTelpUser'=>'085691116373'
        ]);
        // $this->seeJsonStructure([
        //     'UserMobile' => [
        //         'noTelpUser'
        //     ]
        // ]);

    }

    public function test_update_nama()
    {
        $id = '60013ee3d04a000036000133';

        $data = [
            "namaUser"=>"Eri Setiadi Budiawan",
            "flag"=>true
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('PUT','apihijrah/v2/profile/name/'.$id,$data,$header);
        $this->seeStatusCode(200);
        $this->seeJsonContains([
            'statusCode'=>'000',
        ]);
        $this->seeInDatabase('usermobiles',['namaUser'=>'Eri Setiadi Budiawan']);

    }

    public function test_update_telp()
    {
        $id = '60013ee3d04a000036000133';

        $data = [
            "noTelpUser"=>"085691116373"
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('PUT','apihijrah/v2/profile/telp/'.$id,$data,$header);
        $this->seeStatusCode(200);
        $this->seeJsonContains([
            'statusCode'=>'000',
        ]);
        $this->seeInDatabase('usermobiles',["noTelpUser"=>"085691116373"]);

    }

    public function test_update_email()
    {
        $id = '60013ee3d04a000036000133';

        $data = [
            "emailUser"=>"erisetiadibudiawan@gmail.com",
            "flag"=>true
        ];

        $header = [
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $this->json('PUT','apihijrah/v2/profile/email/'.$id,$data,$header);
        $this->seeStatusCode(200);
        $this->seeJsonContains([
            'statusCode'=>'000',
        ]);
        $this->seeInDatabase('usermobiles',["emailUser"=>"erisetiadibudiawan@gmail.com"]);

    }

    public function test_update_foto()
    {
        
        $id = '60013ee3d04a000036000133';
        
        $data = [
            'urlFoto' => UploadedFile::fake()->image('/foto_profil/eri.jpg')
        ];

        $header = [
            'HTTP_Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaWhpanJhaC92Mi9waW4iLCJpYXQiOjE2MTA2OTQ2NDYsImV4cCI6MTYxMDY5NTI0NiwibmJmIjoxNjEwNjk0NjQ2LCJqdGkiOiJhSEVMTXp0Szd4eXVRaXMyIiwic3ViIjoiNjAwMTNlZTNkMDRhMDAwMDM2MDAwMTMzIiwicHJ2IjoiMjJmZWZjNDk4NDYyMmI5Mjk3YmM5NjMwMDJlNWE2MjNiYWJhODgzZSJ9.uPBUZlgU0dYdTPOn4oPte81ZyLPKow4DuudIRTxejX8'
        ];

        $response = $this->call('POST','apihijrah/v2/profile/foto/'.$id,[],[],$data,$header);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'statusCode'=>'000',
        ]);

    }

}
