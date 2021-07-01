<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Entertainments extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'entertainments';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'titleEntertainment', 'contentEntertainment', 'imageEntertainment', 'totalViewerEntertainment', 'publishEntertainment'];
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
    public static $rulesGambarEntertainment = ['imageEntertainment' => 'image'];
    public static $rulesFormatEntertainment = ['imageEntertainment' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxEntertainment    = ['imageEntertainment' => 'max:500'];


    public static $messages =
    [
        'imageEntertainment.image'   => 'Field harus Gambar.',
        'imageEntertainment.mimes'   => 'Format Gambar Salah.',
        'imageEntertainment.max'     => 'Max Gambar 500kb'
    ];

}
