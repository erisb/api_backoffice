<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Trendings extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'trendings';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'titleTrending', 'contentTrending', 'imageTrending', 'totalViewerTrending', 'publishTrending'];
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
    public static $rulesGambarTrending = ['imageTrending' => 'image'];
    public static $rulesFormatTrending = ['imageTrending' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxTrending    = ['imageTrending' => 'max:500'];


    public static $messages =
    [
        'imageTrending.image'   => 'Field harus Gambar.',
        'imageTrending.mimes'   => 'Format Gambar Salah.',
        'imageTrending.max'     => 'Max Gambar 500kb'
    ];

}
