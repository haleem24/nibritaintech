<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Products_model extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'products';
    protected $primaryKey = 'id';
    // public $timestamps = FALSE;
    protected $fillable = [
        // other fields...
        'reference_id',
    ];
    public function variations()
    {
        return $this->hasMany(Variation_model::class, 'product_id', 'id');
    }

    public function order_items()
    {
        return $this->hasManyThrough(Order_item_model::class, Variation_model::class, 'id', 'variation_id', 'id', 'product_id');
    }
}
