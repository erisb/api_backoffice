<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class UmrohOrder extends Model
{
    protected $collection = 'umrohorders';
    protected $primaryKey = '_id';
    protected $fillable = [
        'orderId', 
        'packageId', 
        'orderCode', // genrate from pergi umroh
        'bookingCode', // genrate from Hijrah
        'idUserMobile',
        'room',
        'totalPilgrims',
        'listPilgrims',
        'totalPrice',
        'departureDate',
        'methodPayment',
        'listPayment',
        'status',
        'flag',
        'isCancel',
        'paidOffDate'
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

    public function user_mobiles()
    {
        return $this->hasOne('App\UserMobiles', '_id', 'idUserMobile');
    }
}
