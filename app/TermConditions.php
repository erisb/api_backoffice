<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Jenssegers\Mongodb\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use App\Articles;
use Carbon\Carbon;

class TermConditions extends Model
{
  use Authenticatable, Authorizable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $collection = 'termconditions';
  protected $primaryKey = '_id';
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
  
  protected $fillable = [
    'termContent',
  ];

  /**
   * The attributes excluded from the model's JSON form.
   *
   * @var array
   */
  // protected $hidden = [
  //     'password',
  // ];
}
