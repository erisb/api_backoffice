<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class AtmLocations extends Model
{
    protected $collection = 'atmlocations';
    protected $primaryKey = '_id';
    protected $fillable   = ['namaLokasi', 'latitude', 'longitude', 'imgUrl', 'flag'];
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

    public static $rulesAtmImage           = ['atmImage' => 'image'];
    public static $rulesFormatAtmImage     = ['atmImage' => 'mimes:jpeg,png,jpg,gif'];
    public static $rulesMaxAtmImage        = ['atmImage' => 'max:500'];

    public static $messages =
    [
        'atmImage.image'          => 'Field harus Gambar.',
        'atmImage.mimes'          => 'Format Gambar Salah.',
        'atmImage.max'            => 'Max Gambar 500kb',
    ];
}
