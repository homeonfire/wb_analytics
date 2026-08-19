<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sku extends Model
{
    protected $fillable = [
        'product_id',
        'barcode',
        'tech_size',
        'price',
        'discount'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->hasOne(SkuStock::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(SkuWarehouseStock::class);
    }

    public function warehouseDetails()
    {
        return $this->hasMany(SkuWarehouseDetail::class);
    }
}
