<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'guard_name',
        'store_id'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
