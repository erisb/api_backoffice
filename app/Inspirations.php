<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Inspirations extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'inspirations';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'contentInspiration', 'sourceInspiration', 'imageInspiration', 'statusInspiration', 'meaningInspiration'];
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
    public static $rulesGambarInspirasi = ['imageInspiration' => 'image'];
    public static $rulesFormatInspirasi = ['imageInspiration' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxInspirasi    = ['imageInspiration' => 'max:500'];


    public static $messages =
    [
        'imageInspiration.image'   => 'Field harus Gambar.',
        'imageInspiration.mimes'   => 'Format Gambar Salah.',
        'imageInspiration.max'     => 'Max Gambar 500kb'
    ];

}
