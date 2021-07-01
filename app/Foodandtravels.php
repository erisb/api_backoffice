<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Foodandtravels extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'foodandtravels';
    protected $primaryKey = '_id';
    protected $fillable   = ['categoryId', 'titleFoodandtravel', 'contentFoodandtravel', 'imageFoodandtravel', 'totalViewerFoodandtravel', 'publishFoodandtravel'];
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
    public static $rulesGambarFoodandtravel = ['imageFoodandtravel' => 'image'];
    public static $rulesFormatFoodandtravel = ['imageFoodandtravel' => 'mimes:jpeg,png,jpg,gif,svg'];
    public static $rulesMaxFoodandtravel    = ['imageFoodandtravel' => 'max:500'];


    public static $messages =
    [
        'imageFoodandtravel.image'   => 'Field harus Gambar.',
        'imageFoodandtravel.mimes'   => 'Format Gambar Salah.',
        'imageFoodandtravel.max'     => 'Max Gambar 500kb'
    ];

}
