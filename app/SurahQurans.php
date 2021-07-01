<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class SurahQurans extends Model
{
    protected $collection = 'surahqurans';
    protected $primaryKey = '_id';
    protected $hidden = ['updated_at'];

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
