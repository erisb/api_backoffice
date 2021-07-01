<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class MerchantToken extends Model
{
    protected $collection = 'merchanttokens';
    protected $primaryKey = '_id';
    protected $fillable = ['token_type', 'expires_in', 'access_token'];
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
