<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAnalytic extends Model
{
    protected $fillable = [
        'store_id',
        'nm_id',
        'date',
        'vendor_code',
        'brand_name',
        'object_id',
        'object_name',
        'open_card_count',
        'add_to_cart_count',
        'orders_count',
        'buyouts_count',
        'cancel_count',
        'orders_sum_rub',
        'buyouts_sum_rub',
        'cancel_sum_rub',
        'avg_price_rub',
        'avg_orders_count_per_day',
        'conversion_open_to_cart_percent',
        'conversion_cart_to_order_percent',
        'conversion_buyouts_percent',
        'stocks_mp',
        'stocks_wb'
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
