<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UserMobiles extends Model implements JWTSubject, AuthenticatableContract
{
    use Authenticatable, Authorizable;

    protected $collection = 'usermobiles';
    protected $primaryKey = '_id';
    protected $fillable = [
        'namaUser', 
        'noTelpUser', 
        'emailUser', 
        'pinUser', 
        'urlFoto', 
        'nik', 
        'urlFotoKtp', 
        'urlFotoSelfieKtp', 
        'statusVerifikasi', 
        'imei', 
        'flag'
    ];
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

    public static $rulesNoTelpMin   = ['noTelpUser' => 'min:10'];
    public static $rulesPinUser     = ['pinUser' => 'required'];
    public static $rulesPinMin      = ['pinUser' => 'min:6'];

    public static $rulesNoTelpNumeric   = ['noTelpUser' => 'numeric'];
    public static $rulesPinNumeric      = ['pinUser'  => 'numeric'];

    public static $rulesurlFoto         = ['urlFoto' => 'image'];
    public static $rulesFormaturlFoto   = ['urlFoto' => 'mimes:jpeg,png,jpg,gif'];
    public static $rulesMaxurlFoto      = ['urlFoto' => 'max:500'];

    public static $rulesurlFotoKtp      = ['urlFotoKtp' => 'image'];
    public static $rulesFormatFotoKtp   = ['urlFotoKtp' => 'mimes:jpeg,png,jpg,gif'];
    public static $rulesMaxFotoKtp      = ['urlFotoKtp' => 'max:500'];

    public static $rulesurlFotoSelfieKtp      = ['urlFotoSelfieKtp' => 'image'];
    public static $rulesFormatFotoSelfieKtp   = ['urlFotoSelfieKtp' => 'mimes:jpeg,png,jpg,gif'];
    public static $rulesMaxFotoSelfieKtp      = ['urlFotoSelfieKtp' => 'max:500'];
    
    public static $rulesNikNumeric      = ['nik'  => 'numeric'];
    public static $rulesNikDigit        = ['nik'  => 'digits:16'];

    // REGISTER
    public static $rulesEmail = ['emailUser' => 'email:rfc,dns,filter'];

    public static $messages =
    [
        'noTelpUser.min'            => 'No Telp Kurang dari 10 digit.',
        'noTelpUser.numeric'        => 'No Telp Hanya Angka.',
        'emailUser.email'           => 'Format email salah',
        'urlFoto.image'             => 'Field harus Gambar.',
        'urlFoto.mimes'             => 'Format Gambar Salah.',
        'urlFoto.max'               => 'Max Gambar 500kb',
        'urlFotoKtp.image'          => 'Field harus Gambar.',
        'urlFotoKtp.mimes'          => 'Format Gambar Salah.',
        'urlFotoKtp.max'            => 'Max Gambar 500kb',
        'urlFotoSelfieKtp.image'    => 'Field harus Gambar.',
        'urlFotoSelfieKtp.mimes'    => 'Format Gambar Salah.',
        'urlFotoSelfieKtp.max'      => 'Max Gambar 500kb',
        'pinUser.required'          => 'Input tidak boleh kosong',
        'pinUser.min'               => 'Pin Kurang dari 6 digit',
        'pinUser.numeric'           => 'Pin Hanya Angka',
        'nik.numeric'               => 'NIK Hanya Angka',
        'nik.digits'                 => 'Harus 16 Angka',
    ];


    // public static $rulesCheckEmail =


    /**
     * Generate a JWT token for the user.
     *
     * @return string
     */
    public function getTokenAttribute()
    {
        return JWTAuth::fromUser($this);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function user_token()
    {
        return $this->hasOne('App\UserTokens', 'idUserMobile', '_id');
    }

    public function user_otp()
    {
        return $this->hasOne('App\UserOtps', 'idUserMobile', '_id');
    }

    public function log_user_mobile()
    {
        return $this->hasMany('App\LogUserMobiles', 'idUserMobile', '_id');
    }

    public function umroh_orders()
    {
        return $this->hasMany('App\UmrohOrder', 'idUserMobile', '_id');
    }
}
