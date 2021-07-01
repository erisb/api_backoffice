<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Jenssegers\Mongodb\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use App\Inspirations;
use App\Articles;
use App\UmrohPackage;
use Carbon\Carbon;
use App\Helpers\FormatDate;

class Categories extends Model
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $collection = 'categories';
    protected $primaryKey = '_id';
    protected $fillable = [
        'categoryName','statusCategory'
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

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    // protected $hidden = [
    //     'password',
    // ];
    public function artikel()
    {
        $arr = [];
        $artikel = Articles::where('categoryId', '=', $this->_id)->where('publish','1')->orderBy('created_at', 'DESC')->take(4)->get();
        foreach ($artikel as $data) {
            array_push($arr, [
                "_id"                   => $data->_id,
                "updated_at"            => FormatDate::stringToDate(($data->updated_at)),
                "created_at"            => FormatDate::stringToDate(($data->created_at)),
                'titleItemCategory'     => $data->articleTitle,
                'contentItemCategory'   => $data->articleContent,
                'imageItemCategory'     => $data->articleImage,
                'meaning'               => "",
                "dateItemCategory"      => "",
                "priceItemCategory"     => "",
                'categoryId'            => $data->categoryId,
            ]);
        }
        return $arr;
        // return $this->hasMany('App\Articles', 'idCategory', '_id');
    }
    public function inspirasi()
    {
        $arr_ins = [];
        $ins = Inspirations::where('categoryId', '=', $this->_id)->where('statusInspiration','1')->orderBy('created_at', 'DESC')->take(4)->get();
        foreach($ins as $data){
            array_push($arr_ins, [
                "_id"                   => $data->_id,
                "updated_at"            => FormatDate::stringToDate(($data->updated_at)),
                "created_at"            => FormatDate::stringToDate(($data->created_at)),
                'titleItemCategory'     => $data->sourceInspiration,
                'contentItemCategory'   => $data->contentInspiration,
                'imageItemCategory'     => $data->imageInspiration,
                'meaning'               => $data->meaningInspiration,
                "dateItemCategory"      => "",
                "priceItemCategory"     => "",
                'categoryId'            => $data->categoryId,
            ]);
        }
        return $arr_ins;
    }

    public function searchArtikel($q)
    {
        $arr = [];
        $artikel = Articles::where('articleTitle','like',"%".$q."%")->where('publish','1')->orderBy('created_at', 'DESC')->take(15)->get();
        foreach ($artikel as $data) {
            array_push($arr, [
                "_id"                   => $data->_id,
                "updated_at"            => FormatDate::stringToDate(($data->updated_at)),
                "created_at"            => FormatDate::stringToDate(($data->created_at)),
                'titleItemCategory'     => $data->articleTitle,
                'contentItemCategory'   => $data->articleContent,
                'imageItemCategory'     => $data->articleImage,
                'meaning'               => "",
                "dateItemCategory"      => "",
                "priceItemCategory"     => "",
                'categoryId'            => $data->categoryId,
            ]);
        }
        return $arr;
        // return $this->hasMany('App\Articles', 'idCategory', '_id');
    }
    public function searchInspirasi($q)
    {
        $arr_ins = [];
        $ins = Inspirations::where('contentInspiration','like',"%".$q."%")->where('statusInspiration','1')->orderBy('created_at', 'DESC')->take(15)->get();
        foreach($ins as $data){
            array_push($arr_ins, [
                "_id"                   => $data->_id,
                "updated_at"            => FormatDate::stringToDate(($data->updated_at)),
                "created_at"            => FormatDate::stringToDate(($data->created_at)),
                'titleItemCategory'     => $data->contentInspiration,
                'contentItemCategory'   => $data->sourceInspiration,
                'imageItemCategory'     => $data->imageInspiration,
                'meaning'               => $data->meaningInspiration,
                "dateItemCategory"      => "",
                "priceItemCategory"     => "",
                'categoryId'            => $data->categoryId,
            ]);
        }
        return $arr_ins;
    }

    public function searchUmroh($q)
    {
        $arr_umroh = [];
        $umroh = UmrohPackage::where('name','like',"%".$q."%")->orderBy('departure_date', 'ASC')->take(15)->get();
        foreach($umroh as $val){
            array_push($arr_umroh, [
                "_id"                   => $val->id,
                "updated_at"            => "",
                "created_at"            => "",
                'titleItemCategory'     => $val->name,
                'contentItemCategory'   => "",
                'imageItemCategory'     => $val->image,
                'meaning'               => "",
                "dateItemCategory"      => $val->departure_date,
                "priceItemCategory"     => $val->original_price,
                'categoryId'            => "",
            ]);
        }
        return $arr_umroh;
    }
}
