<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class MenuHomes extends Model
{
    protected $collection = 'menuhomes';
    protected $primaryKey = '_id';
    protected $fillable = ['idMenu', 'judulMenu', 'gambarMenu', 'statusMenu', 'roleMenu'];
    protected $hidden = ['created_at','updated_at'];
    
    public static $rulesgambarMenu = ['gambarMenu' => 'image'];
    public static $rulesFormatMenu = ['gambarMenu' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxMenu    = ['gambarMenu' => 'max:500'];

    public static $messages =
    [
        'gambarMenu.image'   => 'Field harus Gambar.',
        'gambarMenu.mimes'   => 'Format Gambar Salah.',
        'gambarMenu.max'     => 'Max Gambar 500kb'
    ];

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
