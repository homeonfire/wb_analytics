<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertCampaign extends Model
{
    protected $fillable = [
        'store_id',
        'advert_id',
        'name',
        'type',
        'status',
        'daily_budget',
        'create_time',
        'change_time',
        'start_time',
        'end_time',
        'nm_id',
        'subject_id',
        'raw_data'
    ];

    protected $casts = [
        'create_time' => 'datetime',
        'change_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'raw_data' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'nm_id', 'nm_id');
    }

    public function statistics()
    {
        return $this->hasMany(AdvertStatistic::class);
    }
}
