<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncStocksCommand extends Command
{
    protected $signature = 'wb:sync-stocks';
    protected $description = 'Sync FBO stocks from WB';

    public function handle()
    {
        $stores = Store::whereNotNull('api_key_standard')->get();

        foreach ($stores as $store) {
            $this->info("Syncing stocks for store: {$store->name}");
            
            $productMap = Product::where('store_id', $store->id)->pluck('id', 'nm_id')->toArray();
            $nmIds = array_keys($productMap);
            if (empty($nmIds)) continue;

            $chunks = array_chunk($nmIds, 1000);
            $allUpsertData = [];

            foreach ($chunks as $chunk) {
                $retryCount = 0;
                $success = false;
                $response = null;

                while ($retryCount < 10 && !$success) {
                    $response = Http::withHeaders([
                        'Authorization' => $store->api_key_standard,
                        'Accept' => 'application/json'
                    ])->timeout(30)->post('https://seller-analytics-api.wildberries.ru/api/analytics/v1/stocks-report/wb-warehouses', [
                        'nmIds' => $chunk,
                        'limit' => 250000,
                        'offset' => 0
                    ]);

                    if ($response->status() === 429) {
                        $this->warn("Rate limit hit, sleeping 31s...");
                        sleep(31);
                        $retryCount++;
                    } else {
                        $success = true;
                    }
                }

                if (!$success || !$response->successful()) {
                    $this->error("Failed to fetch stocks: " . ($response ? $response->body() : 'No response'));
                    continue;
                }

                $data = $response->json();
                $items = $data['data']['items'] ?? [];

                foreach ($items as $item) {
                    $nmId = $item['nmId'] ?? null;
                    $productId = $productMap[$nmId] ?? null;
                    
                    if (!$productId) continue;

                    $allUpsertData[] = [
                        'product_id' => $productId,
                        'nm_id' => $nmId,
                        'chrt_id' => $item['chrtId'] ?? 0,
                        'warehouse_id' => $item['warehouseId'] ?? 0,
                        'warehouse_name' => $item['warehouseName'] ?? '',
                        'region_name' => $item['regionName'] ?? null,
                        'quantity' => $item['quantity'] ?? 0,
                        'in_way_to_client' => $item['inWayToClient'] ?? 0,
                        'in_way_from_client' => $item['inWayFromClient'] ?? 0,
                        'updated_at' => Carbon::now(),
                    ];
                }

                sleep(31);
            }

            // Zero out current stocks before upserting
            WarehouseStock::whereIn('product_id', array_values($productMap))
                ->update([
                    'quantity' => 0,
                    'in_way_to_client' => 0,
                    'in_way_from_client' => 0
                ]);

            if (!empty($allUpsertData)) {
                $upsertChunks = array_chunk($allUpsertData, 1000);
                foreach ($upsertChunks as $chunk) {
                    WarehouseStock::upsert(
                        $chunk,
                        ['nm_id', 'chrt_id', 'warehouse_id'],
                        ['quantity', 'in_way_to_client', 'in_way_from_client', 'updated_at']
                    );
                }
            }
        }

        $this->info("Stocks sync completed.");
    }
}
