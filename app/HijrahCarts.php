<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class HijrahCarts extends Model
{
    protected $collection = 'hijrahcarts';
    protected $primaryKey = '_id';
    protected $fillable = [
        'packageId',
        'bookingCode',
        'idUserMobile',
        'room',
        'totalPilgrims',
        'listPilgrims',
        'totalPrice',
        'departureDate',
        'flag',
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
