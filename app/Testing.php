<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Testing extends Model
{
    protected $collection = 'testing';
    protected $primaryKey = '_id';
    protected $fillable = ['nama', 'email','no_telp','created_at'];
    public $timestamps = false;

    public static $rules = [
        'nama' => 'required',
        'email' => 'required',
    ];

}
