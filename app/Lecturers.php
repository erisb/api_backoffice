<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class Lecturers extends Model
{
  protected $collection = 'lecturers';
  protected $primaryKey = '_id';
  protected $fillable   = ['lecturerName', 'lecturerAddress', 'lecturerDesc', 'lecturerDateofBirth', 'lecturerPhoto', 'lecturerGallery1', 'lecturerGallery2','lecturerGallery3','lecturerGallery4','lecturerTelp', 'lecturerEmail', 'lecturerAlmamater', 'lecturerSosmed', 'lecturerStatus'];
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

  public static $rulesFotoPenceramah = ['lecturerPhoto', 'lecturerGallery' => 'image'];
  public static $rulesFormatPenceramah = ['lecturerPhoto', 'lecturerGallery' => 'mimes:jpeg,png,jpg,gif,svg'];
  public static $rulesMaxPenceramah    = ['lecturerPhoto', 'lecturerGallery' => 'max:500'];

  public static $messages =
  [
    'lecturerPhoto.image', 'lecturerGallery.image'   => 'Field harus Gambar.',
    'lecturerPhoto.mimes', 'lecturerGallery.mimes'   => 'Format Gambar Salah.',
    'lecturerPhoto.max', 'lecturerGallery.max'    => 'Max Gambar 500kb'
  ];
}
