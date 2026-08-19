<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'store_id',
        'nm_id',
        'vendor_code',
        'title',
        'brand',
        'main_image_url',
        'cost_price',
        'seasonality',
        'margin_30d',
        'revenue_30d',
        'abc_class'
    ];

    protected $casts = [
        'seasonality' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function skus()
    {
        return $this->hasMany(Sku::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function productPlans()
    {
        return $this->hasMany(ProductPlan::class);
    }

    public function externalAdverts()
    {
        return $this->hasMany(ExternalAdvert::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function plans()
    {
        return $this->hasMany(ProductPlan::class);
    }

    public function orderRaws()
    {
        return $this->hasMany(OrderRaw::class, 'nm_id', 'nm_id');
    }

    public function saleRaws()
    {
        return $this->hasMany(SaleRaw::class, 'nm_id', 'nm_id');
    }
}
