<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuWarehouseDetail extends Model
{
    protected $fillable = [
        'sku_id',
        'warehouse_name',
        'quantity'
    ];

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
