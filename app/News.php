<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class News extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'news';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'titleNews', 'contentNews', 'imageNews', 'totalViewerNews', 'publishNews'];
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
    public static $rulesGambarNews = ['imageNews' => 'image'];
    public static $rulesFormatNews = ['imageNews' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxNews    = ['imageNews' => 'max:500'];


    public static $messages =
    [
        'imageNews.image'   => 'Field harus Gambar.',
        'imageNews.mimes'   => 'Format Gambar Salah.',
        'imageNews.max'     => 'Max Gambar 500kb'
    ];

}
