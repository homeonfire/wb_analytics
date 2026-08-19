<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalAdvert extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'blogger_link',
        'ad_cost',
        'ad_spent',
        'platform',
        'formats',
        'release_date',
        'status'
    ];

    protected $casts = [
        'release_date' => 'date',
        'formats' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
