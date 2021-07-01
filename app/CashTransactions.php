<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class CashTransactions extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'cashtransactions';
    protected $primaryKey = '_id';
    protected $fillable   = [
        'idUserMobile', 
        'transactionId', 
        'recipientBank', 
        'recipientAccount', 
        'recipientName', 
        'recvPhoneNumber', 
        'amount', 
        'transactionType',
        'channel',
        'code',
        'trxId',
        'inactive_at',
        'expired_at',
        'status',
        'codeUnik',
        'adminFee',
        'spsBankCode',
        'spsBank',
        'statusTransfer',
        'trxIdTransfer',
        'totalTransaksi'
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
