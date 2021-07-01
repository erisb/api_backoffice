<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class TransactionTopup extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'transactiontopup';
    protected $primaryKey = '_id';
    protected $fillable   = [
        'idUserMobile', 
        'trnsactionId', 
        'refId', 
        'noReferensi', 
        'uuidPurwantara',
        'hp', 
        'codeTopup', 
        'operatorTopup', 
        'nominalTopup',
        'priceTopup', 
        'typeTopup', 
        'messageTopup', 
        'balanceTopup', 
        'detailTopup', 
        'masaAktif', 
        'serialNumber',
        'statusTopup',
        'trName',
        'period',
        'admin',
        'sellingPrice',
        'desc',
        'mpType',
        'datetime',
        'spsBank',
        'codeUnik',
        'totalTransfer',
        'expiredTime',
        'qrString',
        'qrUrl',
        'paymentType',
        'ovoPhoneNumber',
        'ovoStatus',
    ];
    protected $hidden = ['created_at','updated_at'];
    public $timestamps = false;
    protected static function boot()
    {
        parent::boot();
        static::creating(function($post) {
            $post->created_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
            $post->updated_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
        });

        static::updating(function($post) {
            $post->updated_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
        });
    }

}
