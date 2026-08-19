<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = [
        'product_id',
        'nm_id',
        'chrt_id',
        'warehouse_id',
        'warehouse_name',
        'region_name',
        'quantity',
        'in_way_to_client',
        'in_way_from_client'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
