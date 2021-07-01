<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class MerchantTransaction extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'merchanttransactions';
    protected $primaryKey = '_id';
    protected $fillable   = [
        'idUserMobile', 
        'idMasjid', 
        'transactionId', 
        'dokuUuid', 
        'recipientBank', 
        'recipientAccount', 
        'recipientName', 
        'amount', 
        'note', 
        'adminFee', 
        'VANumber', 
        'expiredDate', 
        'purwantaraBankCode', 
        'statusTransfer',
        'statusVA',
        'transactionType',
        'spsBankNumber',
        'spsBankCode',
        'codeUnik',
        'totalTransfer',
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
