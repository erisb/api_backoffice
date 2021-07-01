<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class BackOfficeUserTokens extends Model
{
    protected $collection = 'backofficeusertokens';
    protected $primaryKey = '_id';
    protected $fillable = ['idUserBackOffice', 'token', 'expired'];
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

    public function user_back_office()
    {
        return $this->belongsTo('App\BackOfficeUsers', 'idUserBackOffice');
    }
}
