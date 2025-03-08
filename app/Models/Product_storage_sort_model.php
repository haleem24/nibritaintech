<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Product_storage_sort_model extends Model
{
    use HasFactory;
    protected $table = 'product_storage_sort';
    protected $primaryKey = 'id';
    public $timestamps = FALSE;
    protected $fillable = [
        // other fields...
        'product_id',
        'storage',
        'sort',
    ];
    // protected static function booted()
    // {
    //     static::addGlobalScope(new Status_not_3_scope);
    // }




    public function product(){
        return $this->hasOne(Products_model::class, 'id', 'product_id');
    }
    public function storage_id()
    {
        return $this->hasOne(Storage_model::class, 'id', 'storage');
    }
    public function variations()
    {
        return $this->hasMany(Variation_model::class, 'product_storage_sort_id', 'id');
    }
    public function stocks()
    {
        return $this->hasManyThrough(Stock_model::class, Variation_model::class, 'product_storage_sort_id', 'variation_id', 'id', 'id');
    }


}
