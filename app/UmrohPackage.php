<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class UmrohPackage extends Model
{
    protected $collection   = 'umrohpackages';
    protected $primaryKey   = '_id';
    protected $hidden       = ['_id'];
    protected $fillable     = [
        'id', 
        'image',
        'name', 
        'description',
        'travel_id',
        'travel_name',
        'travel_avatar',
        'travel_umrah_permission',
        'travel_description',
        'travel_address',
        'travel_pilgrims',
        'travel_founded',
        'stock',
        'duration',
        'departure_date',
        'available_seat',
        'original_price',
        'reduced_price',
        'discount',
        'departure_from',
        'transit',
        'arrival_city',
        'origin_arrival_city',
        'departure_city',
        'origin_departure_city',
        'down_payment',
        'rooms',
        'airlines',
        'hotels',
        'itineraries',
        'Is_change_package',
        'notes',
        'is_dummy',
        'flag',
        'created_at'
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
