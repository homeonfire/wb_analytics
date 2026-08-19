<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertStatistic extends Model
{
    protected $fillable = [
        'advert_campaign_id',
        'date',
        'views',
        'clicks',
        'ctr',
        'cpc',
        'spend',
        'atbs',
        'orders',
        'cr',
        'shks',
        'sum_price'
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
