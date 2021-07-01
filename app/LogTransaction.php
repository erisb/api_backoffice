<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class LogTransaction extends Model
{
    protected $collection = 'logtransactions';
    protected $primaryKey = '_id';
    protected $fillable = ['bookingCode', 'idUserMobile', 'totalPrice', 'paymentStatus', 'description', 'flag'];
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
    /* 
        Note Flag: 
        1 = Pergi Umroh
        2 = Transfer OY
        3 = Topup Mobile Pulsa ( PPOB )
    */
}
