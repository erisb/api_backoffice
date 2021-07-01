<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Articles extends Model
{
    protected $collection = 'articles';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'articleTitle', 'articleContent', 'articleImage', 'articleAdmin', 'totalViewer', 'publish'];
    protected $hidden = ['created_at','updated_at'];

    public static $rulesGambarArtikel = ['articleImage' => 'image'];
    public static $rulesFormatArtikel = ['articleImage' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxArtikel    = ['articleImage' => 'max:500'];

    public static $messages =
    [
        'articleImage.image'   => 'Field harus Gambar.',
        'articleImage.mimes'   => 'Format Gambar Salah.',
        'articleImage.max'     => 'Max Gambar 500kb'
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
