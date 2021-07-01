<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class BackOfficeUsers extends Model implements JWTSubject, AuthenticatableContract
{
    use Authenticatable, Authorizable;

    protected $collection = 'backofficeusers';
    protected $primaryKey = '_id';
    protected $fillable = ['namaUser', 'emailUser', 'roleUser'];
    protected $hidden = ['passwordUser','created_at','updated_at'];
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

    public static $rulesEmail = ['emailUser' => 'email'];

    public static $messages =
    [
        'emailUser.email'       => 'Format email salah'
    ];


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
        return $this->hasOne('App\BackOfficeUserTokens', 'idUserBackOffice', '_id');
    }

    public function user_role()
    {
        return $this->belongsTo('App\BackOfficeUserRoles', 'roleUser');
    }

    public function log_user_back_office()
    {
        return $this->hasMany('App\BackOfficeUserLogs', 'idUserBackOffice', '_id');
    }
}
