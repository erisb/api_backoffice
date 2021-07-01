<?php

namespace App\Exports;

use App\LogTransaction;
use App\UmrohOrder;
use App\UserMobiles;
use App\UmrohPackage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class UmrohExport implements FromArray, WithHeadings
{

    protected $fromDt, $toDt;

    function __construct($fromDt, $toDt) {
            $this->fromDt = $fromDt;
            $this->toDt = $toDt;
    }

    public function headings(): array
    {
        return [
            'Booking Code',
            'User ID',
            'Departure Date',
            'Partner Product ID',
            'Product Varian',
            'Package Price',
            'Number of Pilgrims',
            'Total Price',
            'Order Created',
            'Payment Verifed Date',
            'Payment ID',
            'Payment Type',
            'Payment Amount',
            'Total Settlement'
        ];
    }
    
    public function array(): array
    {
            $fromDate = Carbon::createFromDate($this->fromDt);
            $toDate = Carbon::createFromDate($this->toDt)->addDays(1);
            $data = LogTransaction::where('paymentStatus', 0)->whereBetween('created_at',array($fromDate,$toDate))->get();
            $arr = [];
            foreach ($data as $value) {
                $umroh = UmrohOrder::where('bookingCode', $value->bookingCode)->first();
                $package = UmrohPackage::where('id', $umroh->packageId)->first();
                for ($i=0; $i < count($umroh->listPayment); $i++) { 
                    if($umroh->listPayment[$i]['paymentId'] == $value->paymentId){
                        $accDate = $umroh->listPayment[$i]['paymentDate'];
                    }
                }
                $fee = (int) $umroh->totalPilgrims * env('SETTLEMENT');
                if ($value->description == "Pembayaran ke-3") {
                    $settlement = $value->totalPrice - $fee;
                    array_push($arr, [
                        'bookingCode' => $value->bookingCode,
                        'idUserMobile' => $value->idUserMobile,
                        'departureDate' => $umroh->departureDate,
                        'productID' => $umroh->orderId,
                        'rooms' => $umroh->room,
                        'packagePrice' => $package->original_price,
                        'totalPilgrims' => $umroh->totalPilgrims,
                        'totalPrice' => $value->totalPrice,
                        'orderCreated' => $value->created_at,
                        'payVerifedDate' => $accDate,
                        'paymentID' => $value->paymentId,
                        'paymentType' => $value->description,
                        'paymentAmount' => $value->totalPrice,
                        'totalSettlement' => $settlement,
                    ]);
                }else if($value->description == "Pembayaran Penuh"){
                    $settlement = $value->totalPrice - $fee;
                    array_push($arr, [
                        'bookingCode' => $value->bookingCode,
                        'idUserMobile' => $value->idUserMobile,
                        'departureDate' => $umroh->departureDate,
                        'productID' => $umroh->orderId,
                        'rooms' => $umroh->room,
                        'packagePrice' => $package->original_price,
                        'totalPilgrims' => $umroh->totalPilgrims,
                        'totalPrice' => $value->totalPrice,
                        'orderCreated' => $value->created_at,
                        'payVerifedDate' => $accDate,
                        'paymentID' => $value->paymentId,
                        'paymentType' => $value->description,
                        'paymentAmount' => $value->totalPrice,
                        'totalSettlement' => $settlement,
                    ]);
                }else {
                    array_push($arr, [
                        'bookingCode' => $value->bookingCode,
                        'idUserMobile' => $value->idUserMobile,
                        'departureDate' => $umroh->departureDate,
                        'productID' => $umroh->orderId,
                        'rooms' => $umroh->room,
                        'packagePrice' => $package->original_price,
                        'totalPilgrims' => $umroh->totalPilgrims,
                        'totalPrice' => $value->totalPrice,
                        'orderCreated' => $value->created_at,
                        'payVerifedDate' => $accDate,
                        'paymentID' => $value->paymentId,
                        'paymentType' => $value->description,
                        'paymentAmount' => $value->totalPrice,
                        'totalSettlement' => $value->totalPrice,
                    ]);
                }
            }
        return $arr;
    }
}