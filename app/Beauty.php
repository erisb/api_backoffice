<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Beauty extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'beauty';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'titleBeauty', 'contentBeauty', 'imageBeauty', 'totalViewerBeauty', 'publishBeauty'];
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

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public static $rulesGambarBeauty = ['imageBeauty' => 'image'];
    public static $rulesFormatBeauty = ['imageBeauty' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxBeauty    = ['imageBeauty' => 'max:500'];


    public static $messages =
    [
        'imageBeauty.image'   => 'Field harus Gambar.',
        'imageBeauty.mimes'   => 'Format Gambar Salah.',
        'imageBeauty.max'     => 'Max Gambar 500kb'
    ];

}
