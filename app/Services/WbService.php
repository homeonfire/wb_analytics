<?php

namespace App\Services;

use App\Models\Store;
use Dakword\WBSeller\API;

class WbService
{
    public API $api;
    public Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
        
        $this->api = new API([
            'keys' => [
                'content'     => $store->api_key_standard,
                'prices'      => $store->api_key_standard,
                'marketplace' => $store->api_key_standard,
                'statistics'  => $store->api_key_stat,
                'adv'         => $store->api_key_advert,
                'analytics'   => $store->api_key_standard,
            ],
        ]);
    }
}
