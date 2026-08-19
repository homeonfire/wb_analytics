<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'api_key_standard',
        'api_key_stat',
        'api_key_advert'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function externalAdverts()
    {
        return $this->hasMany(ExternalAdvert::class);
    }

    public function advertCampaigns()
    {
        return $this->hasMany(AdvertCampaign::class);
    }

    public function productAnalytics()
    {
        return $this->hasMany(ProductAnalytic::class);
    }

    public function orderRaws()
    {
        return $this->hasMany(OrderRaw::class);
    }

    public function saleRaws()
    {
        return $this->hasMany(SaleRaw::class);
    }
}
