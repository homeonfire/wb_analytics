<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sku;
use App\Services\WbService;
use Carbon\Carbon;

class SyncProductsCommand extends Command
{
    protected $signature = 'wb:sync-products';
    protected $description = 'Sync products and SKUs from WB';

    public function handle()
    {
        $stores = Store::whereNotNull('api_key_standard')->get();

        foreach ($stores as $store) {
            $this->info("Syncing store: {$store->name}");
            $wb = new WbService($store);
            
            $updatedAt = '';
            $nmId = 0;
            
            while (true) {
                try {
                    // Try to pass as limit, updatedAt, nmId if array fails. In newer versions it takes settings array or distinct params.
                    $response = $wb->api->Content()->getCardsList([
                        'settings' => [
                            'cursor' => [
                                'limit' => 100,
                                'updatedAt' => $updatedAt,
                                'nmID' => $nmId
                            ],
                            'filter' => [
                                'withPhoto' => -1
                            ]
                        ]
                    ]);
                } catch (\Exception $e) {
                    $this->error("API Error: " . $e->getMessage());
                    break;
                }

                // If response is object instead of array depending on wrapper
                $cards = is_array($response) ? ($response['cards'] ?? []) : ($response->cards ?? []);
                if (empty($cards)) {
                    break;
                }

                $this->processCards($store, $cards);

                $cursor = is_array($response) ? ($response['cursor'] ?? null) : ($response->cursor ?? null);
                if (!$cursor) {
                    break;
                }

                $newUpdatedAt = is_array($cursor) ? $cursor['updatedAt'] : $cursor->updatedAt;
                $newNmId = is_array($cursor) ? $cursor['nmID'] : $cursor->nmID;

                if ($newUpdatedAt === $updatedAt && $newNmId === $nmId) {
                    break;
                }

                $updatedAt = $newUpdatedAt;
                $nmId = $newNmId;

                if (count($cards) < 100) {
                    break;
                }
            }
        }
        
        $this->info("Products sync completed.");
    }

    private function processCards(Store $store, $cards)
    {
        $productsData = [];
        $skusData = [];

        foreach ($cards as $card) {
            $card = (object) $card;
            $mainImageUrl = null;
            if (!empty($card->photos)) {
                $photo = (object) $card->photos[0];
                if (isset($photo->big)) {
                    $mainImageUrl = $photo->big;
                }
            }

            $productsData[] = [
                'nm_id' => $card->nmID,
                'store_id' => $store->id,
                'vendor_code' => $card->vendorCode ?? 'Без артикула',
                'title' => $card->title ?? 'Без названия',
                'brand' => $card->brand ?? null,
                'main_image_url' => $mainImageUrl,
                'updated_at' => Carbon::now(),
            ];
        }

        if (empty($productsData)) return;

        Product::upsert(
            $productsData,
            ['nm_id'],
            ['store_id', 'vendor_code', 'title', 'brand', 'main_image_url', 'updated_at']
        );

        $nmIds = array_column($productsData, 'nm_id');
        $productMap = Product::whereIn('nm_id', $nmIds)->pluck('id', 'nm_id')->toArray();

        foreach ($cards as $card) {
            $card = (object) $card;
            $productId = $productMap[$card->nmID] ?? null;
            if (!$productId) continue;

            if (!empty($card->sizes)) {
                foreach ($card->sizes as $size) {
                    $size = (object) $size;
                    $techSize = $size->techSize ?? $size->wbSize ?? '-';
                    if (!empty($size->skus)) {
                        foreach ($size->skus as $barcode) {
                            $skusData[] = [
                                'barcode' => $barcode,
                                'product_id' => $productId,
                                'tech_size' => $techSize,
                                'updated_at' => Carbon::now(),
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($skusData)) {
            $chunks = array_chunk($skusData, 1000);
            foreach ($chunks as $chunk) {
                Sku::upsert(
                    $chunk,
                    ['barcode'],
                    ['product_id', 'tech_size', 'updated_at']
                );
            }
        }
    }
}
