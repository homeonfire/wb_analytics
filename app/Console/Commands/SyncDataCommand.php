<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sku;
use App\Services\WbService;
use Illuminate\Support\Facades\DB;

class SyncDataCommand extends Command
{
    protected $signature = 'wb:sync-data {--store=}';
    protected $description = 'Sync prices and discounts from WB';

    public function handle()
    {
        $stores = Store::whereNotNull('api_key_standard')->when($this->option('store'), fn ($query, $storeId) => $query->whereKey($storeId))->get();

        foreach ($stores as $store) {
            $this->info("Syncing prices for store: {$store->name}");
            $wb = new WbService($store);

            try {
                $response = $wb->api->Prices()->getPrices();
            } catch (\Exception $e) {
                $this->error("API Error: " . $e->getMessage());
                continue;
            }

            $listGoods = is_array($response) ? ($response['data']['listGoods'] ?? []) : ($response->data->listGoods ?? []);

            foreach ($listGoods as $good) {
                $good = (object) $good;
                $nmId = $good->nmID ?? null;
                $discount = $good->discount ?? 0;
                $sizes = $good->sizes ?? [];

                if (!$nmId || empty($sizes)) {
                    continue;
                }

                $productId = Product::where('store_id', $store->id)->where('nm_id', $nmId)->value('id');
                if (!$productId) {
                    continue;
                }

                foreach ($sizes as $size) {
                    $size = (object) $size;
                    $techSize = $size->techSizeName ?? null;
                    $price = $size->price ?? null;

                    if ($techSize === null || $price === null) {
                        continue;
                    }

                    Sku::where('product_id', $productId)
                        ->where('tech_size', $techSize)
                        ->update([
                            'price' => $price,
                            'discount' => $discount
                        ]);
                }
            }
        }

        $this->info("Data sync completed.");
    }
}
