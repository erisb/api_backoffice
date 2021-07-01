<?php

namespace App\Http\Controllers\Mail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\UmrohOrder;
use App\UserMobiles;
use App\CashTransactions;

class HijrahEmailController extends Controller
{
	public function approvalMail(Request $req){
      try {
          $data         = UmrohOrder::where('_id', $req->orderId)->first();
          $user         = UserMobiles::where('_id', $data->idUserMobile)->first();
          $arrayPayment = '';
          $count        = count($data->listPayment);
          for ($i=0; $i < $count; $i++) { 
              if ($data->listPayment[$i]['paymentId'] == $req->paymentId) {
                  $arrayPayment = [
                      'index'         => $i,
                      'nama'          => $user->namaUser,
                      'email'         => $user->emailUser,
                      'bookingCode'   => $data->bookingCode,
                      'departureDate' => $data->departureDate,
                      'paymentId'     => $data->listPayment[$i]['paymentId'],
                      'description'   => $data->listPayment[$i]['description'],
                      'due_date'      => $data->listPayment[$i]['due_date'],
                      'billed'        => $data->listPayment[$i]['billed'],
                      'status'        => $data->listPayment[$i]['status'],
                      'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar']
                  ];
              }
          }
          // return $arrayPayment['paymentId'];
          Mail::send([], [], function ($message) use ($arrayPayment, $count) {
          $body = "
          <!doctype html>
          <html>
          <head>
              <meta name='viewport' content='width=device-width' />
              <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
              <title>Boardicle Email</title>
              <style>
                  /* -------------------------------------
                      GLOBAL RESETS
                  ------------------------------------- */
                  img {
                      border: none;
                      -ms-interpolation-mode: bicubic;
                      max-width: 100%; }
          
                  body {
                      background-color: #f6f6f6;
                      font-family: sans-serif;
                      -webkit-font-smoothing: antialiased;
                      font-size: 14px;
                      line-height: 1.4;
                      margin: 0;
                      padding: 0;
                      -ms-text-size-adjust: 100%;
                      -webkit-text-size-adjust: 100%; }
          
                  table {
                      border-collapse: separate;
                      mso-table-lspace: 0pt;
                      mso-table-rspace: 0pt;
                      width: 100%; }
                  table td {
                      font-family: sans-serif;
                      font-size: 14px;
                      vertical-align: top; }
          
                  /* -------------------------------------
                      BODY & CONTAINER
                  ------------------------------------- */
          
                  .body {
                      background-color: #f6f6f6;
                      width: 100%; }
          
                  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
                  .container {
                      display: block;
                      Margin: 0 auto !important;
                      /* makes it centered */
                      max-width: 580px;
                      padding: 10px;
                      width: 580px; }
          
                  /* This should also be a block element, so that it will fill 100% of the .container */
                  .content {
                      box-sizing: border-box;
                      display: block;
                      Margin: 0 auto;
                      max-width: 580px;
                      padding: 10px; }
          
                  /* -------------------------------------
                      HEADER, FOOTER, MAIN
                  ------------------------------------- */
                  .main {
                      background: #ffffff;
                      border-radius: 3px;
                      width: 100%; }
          
                  .wrapper {
                      box-sizing: border-box;
                      padding: 20px; }
          
                  .content-block {
                      padding-bottom: 10px;
                      padding-top: 10px;
                  }
          
                  .footer {
                      clear: both;
                      Margin-top: 10px;
                      text-align: center;
                      width: 100%; }
                  .footer td,
                  .footer p,
                  .footer span,
                  .footer a {
                      color: #999999;
                      font-size: 12px;
                      text-align: center; }
          
                  /* -------------------------------------
                      TYPOGRAPHY
                  ------------------------------------- */
                  h1,
                  h2,
                  h3,
                  h4 {
                      color: #000000;
                      font-family: sans-serif;
                      font-weight: 400;
                      line-height: 1.4;
                      margin: 0;
                      Margin-bottom: 30px; }
          
                  h1 {
                      font-size: 35px;
                      font-weight: 300;
                      text-align: center;
                      text-transform: capitalize; }
          
                  p,
                  ul,
                  ol {
                      font-family: sans-serif;
                      font-size: 14px;
                      font-weight: normal;
                      margin: 0;
                      Margin-bottom: 15px; }
                  p li,
                  ul li,
                  ol li {
                      list-style-position: inside;
                      margin-left: 5px; }
          
                  a {
                      color: #3498db;
                      text-decoration: underline; }
          
                  /* -------------------------------------
                      BUTTONS
                  ------------------------------------- */
                  .btn {
                      box-sizing: border-box;
                      width: 100%; }
                  .btn > tbody > tr > td {
                      padding-bottom: 15px; }
                  .btn table {
                      width: auto; }
                  .btn table td {
                      background-color: #ffffff;
                      border-radius: 5px;
                      text-align: center; }
                  .btn a {
                      background-color: #ffffff;
                      border: solid 1px #3498db;
                      border-radius: 5px;
                      box-sizing: border-box;
                      color: #3498db;
                      cursor: pointer;
                      display: inline-block;
                      font-size: 14px;
                      font-weight: bold;
                      margin: 0;
                      padding: 12px 25px;
                      text-decoration: none;
                      text-transform: capitalize; }
          
                  .btn-primary table td {
                      background-color: #3498db; }
          
                  .btn-primary a {
                      background-color: #3498db;
                      border-color: #3498db;
                      color: #ffffff; }
          
                  /* -------------------------------------
                      OTHER STYLES THAT MIGHT BE USEFUL
                  ------------------------------------- */
                  .last {
                      margin-bottom: 0; }
          
                  .first {
                      margin-top: 0; }
          
                  .align-center {
                      text-align: center; }
          
                  .align-right {
                      text-align: right; }
          
                  .align-left {
                      text-align: left; }
          
                  .clear {
                      clear: both; }
          
                  .mt0 {
                      margin-top: 0; }
          
                  .mb0 {
                      margin-bottom: 0; }
          
                  .preheader {
                      color: transparent;
                      display: none;
                      height: 0;
                      max-height: 0;
                      max-width: 0;
                      opacity: 0;
                      overflow: hidden;
                      mso-hide: all;
                      visibility: hidden;
                      width: 0; }
          
                  .powered-by a {
                      text-decoration: none; }
          
                  hr {
                      border: 0;
                      border-bottom: 1px solid #f6f6f6;
                      Margin: 20px 0; }
          
                  /* -------------------------------------
                      RESPONSIVE AND MOBILE FRIENDLY STYLES
                  ------------------------------------- */
                  @media only screen and (max-width: 620px) {
                      table[class=body] h1 {
                          font-size: 28px !important;
                          margin-bottom: 10px !important; }
                      table[class=body] p,
                      table[class=body] ul,
                      table[class=body] ol,
                      table[class=body] td,
                      table[class=body] span,
                      table[class=body] a {
                          font-size: 16px !important; }
                      table[class=body] .wrapper,
                      table[class=body] .article {
                          padding: 10px !important; }
                      table[class=body] .content {
                          padding: 0 !important; }
                      table[class=body] .container {
                          padding: 0 !important;
                          width: 100% !important; }
                      table[class=body] .main {
                          border-left-width: 0 !important;
                          border-radius: 0 !important;
                          border-right-width: 0 !important; }
                      table[class=body] .btn table {
                          width: 100% !important; }
                      table[class=body] .btn a {
                          width: 100% !important; }
                      table[class=body] .img-responsive {
                          height: auto !important;
                          max-width: 100% !important;
                          width: auto !important; }}
          
                  /* -------------------------------------
                      PRESERVE THESE STYLES IN THE HEAD
                  ------------------------------------- */
                  @media all {
                      .ExternalClass {
                          width: 100%; }
                      .ExternalClass,
                      .ExternalClass p,
                      .ExternalClass span,
                      .ExternalClass font,
                      .ExternalClass td,
                      .ExternalClass div {
                          line-height: 100%; }
                      .apple-link a {
                          color: inherit !important;
                          font-family: inherit !important;
                          font-size: inherit !important;
                          font-weight: inherit !important;
                          line-height: inherit !important;
                          text-decoration: none !important; }
                      .btn-primary table td:hover {
                          background-color: #34495e !important; }
                      .btn-primary a:hover {
                          background-color: #34495e !important;
                          border-color: #34495e !important; } }
          
              </style>
          </head>
          <body class=''>
          <table border='0' cellpadding='0' cellspacing='0' class='body'>
              <tr>
                  <td>&nbsp;</td>
                  <td class='container'>
                      <div class='content'>
          
                          <!-- START CENTERED WHITE CONTAINER -->
                          <span class='preheader'>Email Untuk Kamu !.</span>
                          <table class='main'>
          
                              <!-- START MAIN CONTENT AREA -->
                              <tr>
                                  <td class='wrapper'>
                                      <table border='0' cellpadding='0' cellspacing='0'>
                                          <tr>
                                              <td>
                                                  <p>Assalamu'alaikum Warohmatullah Wabarokatuh</p>
                                                  <p>Dear  ".$arrayPayment['nama']."</p>
                                                  <table border='0' cellpadding='0' cellspacing='0'>
                                                      <tbody>
                                                      <tr>
                                                          <td align='left'>
                                                              <table border='0' cellpadding='0' cellspacing='0'>
                                                                  <tbody>
                                                                  <tr>
                                                                      <td> <div class='container'>";
                                                                          if ($count > 1) {
                                                                              if ($arrayPayment["index"] == 0) {
                                                                              $body .= "<p>Terima Kasih atas Pembayaran DP PergiUmroh.</p>";
                                                                              }else if($arrayPayment["index"] == 1){
                                                                              $body .= "<p>Terima Kasih atas Pembayaran 2 PergiUmroh.</p>";
                                                                              }else if($arrayPayment["index"] == 2){
                                                                              $body .= "<p>Terima Kasih atas Pembayaran 3 PergiUmroh.</p>";
                                                                              }
                                                                          }else{
                                                                              $body .= "<p>Terima Kasih atas Pembayaran Penuh PergiUmroh.</p>";
                                                                          }
                                                                              
                                                                          $body .= "<p>Berikut informasi detilnya :</p>
                                                                              <hr>
                                                                              <p>Payment ID <span style='display:inline-block;'>= ".$arrayPayment["paymentId"]."</span></p>
                                                                              <p>Booking Kode <span style='display:inline-block;'>= ".$arrayPayment["bookingCode"]."</span></p>";
                                                                              if ($count > 1) {
                                                                                  if ($arrayPayment["index"] == 0) {
                                                                                  $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= DP</span></p>";
                                                                                  }else if($arrayPayment["index"] == 1){
                                                                                  $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran 2</span></p>";
                                                                                  }else if($arrayPayment["index"] == 2){
                                                                                  $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran 3</span></p>";
                                                                                  }
                                                                              }else{
                                                                                  $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran Penuh</span></p>";
                                                                              }
                                                                              $body .=  "
                                                                              <p>Jumlah Bayar <span style='display:inline-block;'>= Rp. ".number_format($arrayPayment['billed'],2,',','.')."</span></p>
                                                                              <p>Status <span style='display:inline-block;'>= ".$arrayPayment['description']."</span></p>
                                                                              <p>Tanggal Keberangkatan <span style='display:inline-block;'>= ".date('d M Y', strtotime($arrayPayment['departureDate']))."</span></p>
                                                                              <hr>
                                                                              <p> Wassalamu'alaikum Warohmatullah Wabarokatuh</p>
                                                                          </div>
                                                                      </td>
                                                                  </tr>
                                                                  </tbody>
                                                              </table>
                                                          </td>
                                                      </tr>
                                                      </tbody>
                                                  </table>
                                              </td>
                                          </tr>
                                      </table>
                                  </td>
                              </tr>
          
                              <!-- END MAIN CONTENT AREA -->
                          </table>
          
                          <!-- START FOOTER -->
                          <div class='footer'>
                              <table border='0' cellpadding='0' cellspacing='0'>
                                  <tr>
                                      <td class='content-block'>
                                          <span class='apple-link'>HIJRAH APP</span>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td class='content-block powered-by'>
                                      </td>
                                  </tr>
                              </table>
                          </div>
                          <!-- END FOOTER -->
          
                          <!-- END CENTERED WHITE CONTAINER -->
                      </div>
                  </td>
                  <td>&nbsp;</td>
              </tr>
          </table>
          </body>
          </html>

          ";
          $pesan = $message->to($arrayPayment['email']);
          if ($count > 1) {
              if ($arrayPayment["index"] == 0) {
              $pesan->subject('Konfirmasi Pembayaran DP');
              }else if($arrayPayment["index"] == 1){
              $pesan->subject('Konfirmasi Pembayaran 2');
              }else if($arrayPayment["index"] == 2){
              $pesan->subject('Konfirmasi Pembayaran 3');
              }
          }else{
              $pesan->subject('Konfirmasi Pembayaran Penuh');
          }
              
              // here comes what you want
              // ->setBody('Hi, welcome user!') // assuming text/plain
              // or:
              $pesan->setBody($body, 'text/html'); // for HTML rich messages
          });

          $response = "Email Approved telah dikirim";
      } catch (RequestException $e) {
          $errorCode = $e->getCode();
          $message = $e->getMessage();
          \Sentry\captureException($e);
          $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
      }
      return $response;
  }
  
  public function rejectedMail(Request $req){
    try {
        $data         = UmrohOrder::where('_id', $req->orderId)->first();
        $user         = UserMobiles::where('_id', $data->idUserMobile)->first();
        $arrayPayment = '';
        $count        = count($data->listPayment);
        for ($i=0; $i < $count; $i++) { 
        if ($data->listPayment[$i]['paymentId'] == $req->paymentId) {
            $arrayPayment = [
                'index'         => $i,
                'nama'          => $user->namaUser,
                'email'         => $user->emailUser,
                'bookingCode'   => $data->bookingCode,
                'departureDate' => $data->departureDate,
                'paymentId'     => $data->listPayment[$i]['paymentId'],
                'description'   => $data->listPayment[$i]['description'],
                'due_date'      => $data->listPayment[$i]['due_date'],
                'billed'        => $data->listPayment[$i]['billed'],
                'status'        => $data->listPayment[$i]['status'],
                'urlBuktiBayar' => $data->listPayment[$i]['urlBuktiBayar']
                ];
            }
        }
        // return $arrayPayment['paymentId'];
        Mail::send([], [], function ($message) use ($arrayPayment, $count) {
        $body = "
        <!doctype html>
        <html>
        <head>
            <meta name='viewport' content='width=device-width' />
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
            <title>Boardicle Email</title>
            <style>
                /* -------------------------------------
                    GLOBAL RESETS
                ------------------------------------- */
                img {
                    border: none;
                    -ms-interpolation-mode: bicubic;
                    max-width: 100%; }
        
                body {
                    background-color: #f6f6f6;
                    font-family: sans-serif;
                    -webkit-font-smoothing: antialiased;
                    font-size: 14px;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                    -ms-text-size-adjust: 100%;
                    -webkit-text-size-adjust: 100%; }
        
                table {
                    border-collapse: separate;
                    mso-table-lspace: 0pt;
                    mso-table-rspace: 0pt;
                    width: 100%; }
                table td {
                    font-family: sans-serif;
                    font-size: 14px;
                    vertical-align: top; }
        
                /* -------------------------------------
                    BODY & CONTAINER
                ------------------------------------- */
        
                .body {
                    background-color: #f6f6f6;
                    width: 100%; }
        
                /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
                .container {
                    display: block;
                    Margin: 0 auto !important;
                    /* makes it centered */
                    max-width: 580px;
                    padding: 10px;
                    width: 580px; }
        
                /* This should also be a block element, so that it will fill 100% of the .container */
                .content {
                    box-sizing: border-box;
                    display: block;
                    Margin: 0 auto;
                    max-width: 580px;
                    padding: 10px; }
        
                /* -------------------------------------
                    HEADER, FOOTER, MAIN
                ------------------------------------- */
                .main {
                    background: #ffffff;
                    border-radius: 3px;
                    width: 100%; }
        
                .wrapper {
                    box-sizing: border-box;
                    padding: 20px; }
        
                .content-block {
                    padding-bottom: 10px;
                    padding-top: 10px;
                }
        
                .footer {
                    clear: both;
                    Margin-top: 10px;
                    text-align: center;
                    width: 100%; }
                .footer td,
                .footer p,
                .footer span,
                .footer a {
                    color: #999999;
                    font-size: 12px;
                    text-align: center; }
        
                /* -------------------------------------
                    TYPOGRAPHY
                ------------------------------------- */
                h1,
                h2,
                h3,
                h4 {
                    color: #000000;
                    font-family: sans-serif;
                    font-weight: 400;
                    line-height: 1.4;
                    margin: 0;
                    Margin-bottom: 30px; }
        
                h1 {
                    font-size: 35px;
                    font-weight: 300;
                    text-align: center;
                    text-transform: capitalize; }
        
                p,
                ul,
                ol {
                    font-family: sans-serif;
                    font-size: 14px;
                    font-weight: normal;
                    margin: 0;
                    Margin-bottom: 15px; }
                p li,
                ul li,
                ol li {
                    list-style-position: inside;
                    margin-left: 5px; }
        
                a {
                    color: #3498db;
                    text-decoration: underline; }
        
                /* -------------------------------------
                    BUTTONS
                ------------------------------------- */
                .btn {
                    box-sizing: border-box;
                    width: 100%; }
                .btn > tbody > tr > td {
                    padding-bottom: 15px; }
                .btn table {
                    width: auto; }
                .btn table td {
                    background-color: #ffffff;
                    border-radius: 5px;
                    text-align: center; }
                .btn a {
                    background-color: #ffffff;
                    border: solid 1px #3498db;
                    border-radius: 5px;
                    box-sizing: border-box;
                    color: #3498db;
                    cursor: pointer;
                    display: inline-block;
                    font-size: 14px;
                    font-weight: bold;
                    margin: 0;
                    padding: 12px 25px;
                    text-decoration: none;
                    text-transform: capitalize; }
        
                .btn-primary table td {
                    background-color: #3498db; }
        
                .btn-primary a {
                    background-color: #3498db;
                    border-color: #3498db;
                    color: #ffffff; }
        
                /* -------------------------------------
                    OTHER STYLES THAT MIGHT BE USEFUL
                ------------------------------------- */
                .last {
                    margin-bottom: 0; }
        
                .first {
                    margin-top: 0; }
        
                .align-center {
                    text-align: center; }
        
                .align-right {
                    text-align: right; }
        
                .align-left {
                    text-align: left; }
        
                .clear {
                    clear: both; }
        
                .mt0 {
                    margin-top: 0; }
        
                .mb0 {
                    margin-bottom: 0; }
        
                .preheader {
                    color: transparent;
                    display: none;
                    height: 0;
                    max-height: 0;
                    max-width: 0;
                    opacity: 0;
                    overflow: hidden;
                    mso-hide: all;
                    visibility: hidden;
                    width: 0; }
        
                .powered-by a {
                    text-decoration: none; }
        
                hr {
                    border: 0;
                    border-bottom: 1px solid #f6f6f6;
                    Margin: 20px 0; }
        
                /* -------------------------------------
                    RESPONSIVE AND MOBILE FRIENDLY STYLES
                ------------------------------------- */
                @media only screen and (max-width: 620px) {
                    table[class=body] h1 {
                        font-size: 28px !important;
                        margin-bottom: 10px !important; }
                    table[class=body] p,
                    table[class=body] ul,
                    table[class=body] ol,
                    table[class=body] td,
                    table[class=body] span,
                    table[class=body] a {
                        font-size: 16px !important; }
                    table[class=body] .wrapper,
                    table[class=body] .article {
                        padding: 10px !important; }
                    table[class=body] .content {
                        padding: 0 !important; }
                    table[class=body] .container {
                        padding: 0 !important;
                        width: 100% !important; }
                    table[class=body] .main {
                        border-left-width: 0 !important;
                        border-radius: 0 !important;
                        border-right-width: 0 !important; }
                    table[class=body] .btn table {
                        width: 100% !important; }
                    table[class=body] .btn a {
                        width: 100% !important; }
                    table[class=body] .img-responsive {
                        height: auto !important;
                        max-width: 100% !important;
                        width: auto !important; }}
        
                /* -------------------------------------
                    PRESERVE THESE STYLES IN THE HEAD
                ------------------------------------- */
                @media all {
                    .ExternalClass {
                        width: 100%; }
                    .ExternalClass,
                    .ExternalClass p,
                    .ExternalClass span,
                    .ExternalClass font,
                    .ExternalClass td,
                    .ExternalClass div {
                        line-height: 100%; }
                    .apple-link a {
                        color: inherit !important;
                        font-family: inherit !important;
                        font-size: inherit !important;
                        font-weight: inherit !important;
                        line-height: inherit !important;
                        text-decoration: none !important; }
                    .btn-primary table td:hover {
                        background-color: #34495e !important; }
                    .btn-primary a:hover {
                        background-color: #34495e !important;
                        border-color: #34495e !important; } }
            
                </style>
            </head>
            <body class=''>
            <table border='0' cellpadding='0' cellspacing='0' class='body'>
                <tr>
                    <td>&nbsp;</td>
                    <td class='container'>
                        <div class='content'>
            
                            <!-- START CENTERED WHITE CONTAINER -->
                            <span class='preheader'>Email Untuk Kamu !.</span>
                            <table class='main'>
            
                                <!-- START MAIN CONTENT AREA -->
                                <tr>
                                    <td class='wrapper'>
                                        <table border='0' cellpadding='0' cellspacing='0'>
                                            <tr>
                                                <td>
                                                    <p>Assalamu'alaikum Warohmatullah Wabarokatuh</p>
                                                    <p>Dear  ".$arrayPayment['nama']."</p>
                                                    <table border='0' cellpadding='0' cellspacing='0'>
                                                        <tbody>
                                                        <tr>
                                                            <td align='left'>
                                                                <table border='0' cellpadding='0' cellspacing='0'>
                                                                    <tbody>
                                                                    <tr>
                                                                        <td> <div class='container'>";
                                                                            if ($count > 1) {
                                                                                if ($arrayPayment["index"] == 0) {
                                                                                $body .= "<p>Pembayaran DP PergiUmroh Anda Ditolak.</p>";
                                                                                }else if($arrayPayment["index"] == 1){
                                                                                $body .= "<p>Pembayaran 2 PergiUmroh Anda Ditolak.</p>";
                                                                                }else if($arrayPayment["index"] == 2){
                                                                                $body .= "<p>Pembayaran 3 PergiUmroh Anda Ditolak.</p>";
                                                                                }
                                                                            }else{
                                                                                $body .= "<p>Pembayaran Penuh PergiUmroh Anda Ditolak.</p>";
                                                                            }
                                                                                
                                                                            $body .= "<p>Berikut informasi detilnya :</p>
                                                                                <hr>
                                                                                <p>Payment ID <span style='display:inline-block;'>= ".$arrayPayment["paymentId"]."</span></p>
                                                                                <p>Booking Kode <span style='display:inline-block;'>= ".$arrayPayment["bookingCode"]."</span></p>";
                                                                                if ($count > 1) {
                                                                                    if ($arrayPayment["index"] == 0) {
                                                                                    $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= DP</span></p>";
                                                                                    }else if($arrayPayment["index"] == 1){
                                                                                    $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran 2</span></p>";
                                                                                    }else if($arrayPayment["index"] == 2){
                                                                                    $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran 3</span></p>";
                                                                                    }
                                                                                }else{
                                                                                    $body .= "<p>Jenis Tagihan <span style='display:inline-block;'>= Pembayaran Penuh</span></p>";
                                                                                }
                                                                                $body .=  "
                                                                                <p>Jumlah Bayar <span style='display:inline-block;'>= Rp. ".number_format($arrayPayment['billed'],2,',','.')."</span></p>
                                                                                <p>Status <span style='display:inline-block;'>= ".$arrayPayment['description']."</span></p>
                                                                                <p>Tanggal Keberangkatan <span style='display:inline-block;'>= ".date('d M Y', strtotime($arrayPayment['departureDate']))."</span></p>
                                                                                <hr>
                                                                                <p> Wassalamu'alaikum Warohmatullah Wabarokatuh</p>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
            
                                <!-- END MAIN CONTENT AREA -->
                            </table>
            
                            <!-- START FOOTER -->
                            <div class='footer'>
                                <table border='0' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td class='content-block'>
                                            <span class='apple-link'>HIJRAH APP</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class='content-block powered-by'>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- END FOOTER -->
            
                            <!-- END CENTERED WHITE CONTAINER -->
                        </div>
                    </td>
                    <td>&nbsp;</td>
                </tr>
            </table>
            </body>
            </html>

            ";
            $pesan = $message->to($arrayPayment['email']);
            if ($count > 1) {
                if ($arrayPayment["index"] == 0) {
                $pesan->subject('Konfirmasi Pembayaran DP');
                }else if($arrayPayment["index"] == 1){
                $pesan->subject('Konfirmasi Pembayaran 2');
                }else if($arrayPayment["index"] == 2){
                $pesan->subject('Konfirmasi Pembayaran 3');
                }
            }else{
                $pesan->subject('Konfirmasi Pembayaran Penuh');
            }
                
                // here comes what you want
                // ->setBody('Hi, welcome user!') // assuming text/plain
                // or:
                $pesan->setBody($body, 'text/html'); // for HTML rich messages
            });

            $response = "Email Reject telah dikirim";
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
	}

    
public function sendEmailSetorTarik($id){
    try {
        $data         = CashTransactions::where('_id', $id)->first();
        $user         = UserMobiles::where('_id', $data->idUserMobile)->first();

        Mail::send([], [], function ($message) use ($data, $user){
        $body = "
        <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
        <html xmlns='http://www.w3.org/1999/xhtml'>
          <head>
            <title>WELCOME</title>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
            <meta http-equiv='X-UA-Compatible' content='IE=edge' />
            <meta name='viewport' content='width=device-width, initial-scale=1.0 ' />
            <meta name='format-detection' content='telephone=no' />
            <!--[if !mso]><!-->
            <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700' rel='stylesheet' />
            <!--<![endif]-->
            <style type='text/css'>
              body {
              -webkit-text-size-adjust: 100% !important;
              -ms-text-size-adjust: 100% !important;
              -webkit-font-smoothing: antialiased !important;
              }
              img {
              border: 0 !important;
              outline: none !important;
              }
              p {
              Margin: 0px !important;
              Padding: 0px !important;
              }
              table {
              border-collapse: collapse;
              mso-table-lspace: 0px;
              mso-table-rspace: 0px;
              }
              td, a, span {
              border-collapse: collapse;
              mso-line-height-rule: exactly;
              }
              .ExternalClass * {
              line-height: 100%;
              }
              span.MsoHyperlink {
              mso-style-priority:99;
              color:inherit;}
              span.MsoHyperlinkFollowed {
              mso-style-priority:99;
              color:inherit;}
              </style>
              <style media='only screen and (min-width:481px) and (max-width:599px)' type='text/css'>
              @media only screen and (min-width:481px) and (max-width:599px) {
              table[class=em_main_table] {
              width: 100% !important;
              }
              table[class=em_wrapper] {
              width: 100% !important;
              }
              td[class=em_hide], br[class=em_hide] {
              display: none !important;
              }
              img[class=em_full_img] {
              width: 100% !important;
              height: auto !important;
              }
              td[class=em_align_cent] {
              text-align: center !important;
              }
              td[class=em_aside]{
              padding-left:10px !important;
              padding-right:10px !important;
              }
              td[class=em_height]{
              height: 20px !important;
              }
              td[class=em_font]{
              font-size:14px !important;	
              }
              td[class=em_align_cent1] {
              text-align: center !important;
              padding-bottom: 10px !important;
              }
              }
              </style>
              <style media='only screen and (max-width:480px)' type='text/css'>
              @media only screen and (max-width:480px) {
              table[class=em_main_table] {
              width: 100% !important;
              }
              table[class=em_wrapper] {
              width: 100% !important;
              }
              td[class=em_hide], br[class=em_hide], span[class=em_hide] {
              display: none !important;
              }
              img[class=em_full_img] {
              width: 100% !important;
              height: auto !important;
              }
              td[class=em_align_cent] {
              text-align: center !important;
              }
              td[class=em_align_cent1] {
              text-align: center !important;
              padding-bottom: 10px !important;
              }
              td[class=em_height]{
              height: 20px !important;
              }
              td[class=em_aside]{
              padding-left:10px !important;
              padding-right:10px !important;
              } 
              td[class=em_font]{
              font-size:14px !important;
              line-height:28px !important;
              }
              span[class=em_br]{
              display:block !important;
              }
              }
            </style>
          </head>
          <body style='margin:0px; padding:0px;' bgcolor='#ffffff'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#ffffff'>
              <!-- === PRE HEADER SECTION=== -->  
              <tr>
                <td align='center' valign='top'  bgcolor='#30373b'>
                  <table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='em_main_table' style='table-layout:fixed;'>
                    <tr>
                      <td style='line-height:0px; font-size:0px;' width='600' class='em_hide' bgcolor='#30373b'><img src='images/spacer.gif' height='1'  width='600' style='max-height:1px; min-height:1px; display:block; width:600px; min-width:600px;' border='0' alt='' /></td>
                    </tr>
                    <tr>
                      <td valign='top'>
                        <table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='em_wrapper'>
                          <tr>
                            <td height='10' class='em_height' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td valign='top'>
                              <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                <tr>
                                  <td valign='top'>
                                    <table width='150' border='0' cellspacing='0' cellpadding='0' align='right' class='em_wrapper'>
                                      <tr>
                                        <td align='right' class='em_align_cent1' style='font-family:'Open Sans', Arial, sans-serif; font-size:12px; line-height:16px; color:#848789; text-decoration:underline;'>
                                          <a href='#' target='_blank' style='text-decoration:underline; color:#848789;'>View online</a>
                                        </td>
                                      </tr>
                                    </table>
                                    <table width='400' border='0' cellspacing='0' cellpadding='0' align='left' class='em_wrapper'>
                                      <tr>
                                        <td align='left' class='em_align_cent' style='font-family:'Open Sans', Arial, sans-serif; font-size:12px; line-height:18px; color:#848789; text-decoration:none;'>
                                          Snippet text here lorem ipsum is dummy text
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                          <tr>
                            <td height='10' class='em_height' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <!-- === //PRE HEADER SECTION=== -->  
              <!-- === BODY SECTION=== --> 
              <tr>
                <td align='center' valign='top'  bgcolor='#ffffff'>
                  <table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='em_main_table' style='table-layout:fixed;'>
                    <!-- === LOGO SECTION === -->
                    <tr>
                      <td height='40' class='em_height'>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align='center'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://hijrahnuswantara.com/images/logo.png' width='230' height='100' style='display:block;font-family: Arial, sans-serif; font-size:15px; line-height:18px; color:#30373b;  font-weight:bold;' border='0' alt='LoGo Here' /></a></td>
                    </tr>
                    <!-- === //LOGO SECTION === -->
                    <!-- === NEVIGATION SECTION === -->
                    <!-- === //NEVIGATION SECTION === -->
                    <!-- === IMG WITH TEXT AND COUPEN CODE SECTION === -->
                    <tr>
                      <td valign='top' class='em_aside'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                          <tr>
                            <td height='41' class='em_height'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td height='1' bgcolor='#d8e4f0' style='font-size:0px;line-height:0px;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/spacer.gif' width='1' height='1' alt='' style='display:block;' border='0' /></td>
                          </tr>
                          <tr>
                            <td height='35' class='em_height'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:15px; font-weight:bold; line-height:18px; color:#30373b;'>Selamat Datang &lt;".$user->namaUser."&gt;</td>
                          </tr>
                          <tr>
                            <td height='22' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:18px; font-weight:bold; line-height:20px; color:#feae39;'>Kode ini rahasia & Tidak boleh di bagikan ke siapapun !!</td>
                          </tr>
                          <tr>
                            <td height='20' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:18px; line-height:20px; color:#feae39;'>Kode Kamu</td>
                          </tr>
                          <tr>
                            <td height='12' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td valign='top' align='center'>
                              <table width='210' border='0' cellspacing='0' cellpadding='0' align='center'>
                                <tr>
                                  <td valign='middle' align='center' height='45' bgcolor='#146259' style='font-family:'Open Sans', Arial, sans-serif; font-size:17px; font-weight:bold; color:#ffffff; text-transform:uppercase;'>".$data->code."</td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                          <tr>
                            <td height='34' class='em_height'>&nbsp;</td>
                          </tr>
                          <tr>
                            <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:15px; line-height:22px; color:#999999;'>Terimakasih telah melakukan prosedur<br class='em_hide'/>
                              yang di tetapkan oleh Hijrah Nuswantara.<br class='em_hide' />
                            </td>
                          </tr>
                          <tr>
                            <td height='31' class='em_height'>&nbsp;</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <!-- === //IMG WITH TEXT AND COUPEN CODE SECTION === -->
                  </table>
                </td>
              </tr>
              <!-- === //BODY SECTION=== -->
              <!-- === FOOTER SECTION === -->
              <tr>
                <td align='center' valign='top'  bgcolor='#30373b' class='em_aside'>
                  <table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='em_main_table' style='table-layout:fixed;'>
                    <tr>
                      <td height='35' class='em_height'>&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign='top' align='center'>
                        <table border='0' cellspacing='0' cellpadding='0' align='center'>
                          <tr>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/fb.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='Fb' /></a></td>
                            <td width='7'>&nbsp;</td>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/tw.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='Tw' /></a></td>
                            <td width='7'>&nbsp;</td>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/pint.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='pint' /></a></td>
                            <td width='7'>&nbsp;</td>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/google.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='G+' /></a></td>
                            <td width='7'>&nbsp;</td>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/insta.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='Insta' /></a></td>
                            <td width='7'>&nbsp;</td>
                            <td valign='top'><a href='#' target='_blank' style='text-decoration:none;'><img src='https://www.sendwithus.com/assets/img/emailmonks/images/yt.png' width='26' height='26' style='display:block;font-family: Arial, sans-serif; font-size:10px; line-height:18px; color:#feae39; ' border='0' alt='Yt' /></a></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td height='22' class='em_height'>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:12px; line-height:18px; color:#848789; text-transform:uppercase;'>
                      <span style='text-decoration:underline;'><a href='#' target='_blank' style='text-decoration:underline; color:#848789;'>PRIVACY STATEMENT</a></span> &nbsp;&nbsp;|&nbsp;&nbsp; <span style='text-decoration:underline;'><a href='#' target='_blank' style='text-decoration:underline; color:#848789;'>TERMS OF SERVICE</a></span><span class='em_hide'> &nbsp;&nbsp;|&nbsp;&nbsp; </span><span class='em_br'></span><span style='text-decoration:underline;'><a href='#' target='_blank' style='text-decoration:underline; color:#848789;'>RETURNS</a></span>
                      </td>
                    </tr>
                    <tr>
                      <td height='10' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:12px; line-height:18px; color:#848789;text-transform:uppercase;'>
                        &copy;2&zwnj;016 company name. All Rights Reserved.
                      </td>
                    </tr>
                    <tr>
                      <td height='10' style='font-size:1px; line-height:1px;'>&nbsp;</td>
                    </tr>
                    <tr>
                      <td align='center' style='font-family:'Open Sans', Arial, sans-serif; font-size:12px; line-height:18px; color:#848789;text-transform:uppercase;'>
                        If you do not wish to receive any further emails from us, please  <span style='text-decoration:underline;'><a href='#' target='_blank' style='text-decoration:underline; color:#848789;'>unsubscribe</a></span>
                      </td>
                    </tr>
                    <tr>
                      <td height='35' class='em_height'>&nbsp;</td>
                    </tr>
                  </table>
                </td>
              </tr>
              <!-- === //FOOTER SECTION === -->
            </table>
            <div style='display:none; white-space:nowrap; font:20px courier; color:#ffffff; background-color:#ffffff;'>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
          </body>
        </html>
            ";
            $pesan = $message->to([$user->emailUser]);
            // if ($count > 1) {
            //     if ($arrayPayment['index'] == 0) {
            //     $pesan->subject('Konfirmasi Pembayaran DP');
            //     }else if($arrayPayment['index'] == 1){
            //     $pesan->subject('Konfirmasi Pembayaran 2');
            //     }else if($arrayPayment['index'] == 2){
            //     $pesan->subject('Konfirmasi Pembayaran 3');
            //     }
            // }else{
                $pesan->subject('Setor/Tarik No. Transaksi : '.$data->transactionId);
            // }
                
                // here comes what you want
                // ->setBody('Hi, welcome user!') // assuming text/plain
                // or:
                $pesan->setBody($body, 'text/html'); // for HTML rich messages
            });

            $response = "Email telah dikirim";
            
        } catch (RequestException $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
	}

}