<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class MootaMutations extends Model
{
    protected $collection = 'mootamutations';
    protected $primaryKey = '_id';
    protected $fillable   = ['mutationId', 'bankId', 'accountNumber', 'bankType', 'date', 'amount', 'description','type','balance'];
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
