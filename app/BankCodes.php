<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class BankCodes extends Model

{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'bankcodes';
    protected $primaryKey = '_id';
    protected $fillable   = ['bankCode', 'bankName', 'bankImage'];
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
    
    public static $rulesBankImage           = ['bankImage' => 'image'];
    public static $rulesFormatBankImage     = ['bankImage' => 'mimes:jpeg,png,jpg,gif'];
    public static $rulesMaxBankImage        = ['bankImage' => 'max:500'];

    public static $messages =
    [
        'bankImage.image'          => 'Field harus Gambar.',
        'bankImage.mimes'          => 'Format Gambar Salah.',
        'bankImage.max'            => 'Max Gambar 500kb',
    ];
}
