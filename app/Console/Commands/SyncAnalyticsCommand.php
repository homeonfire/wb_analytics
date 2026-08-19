<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductAnalytic;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncAnalyticsCommand extends Command
{
    protected $signature = 'wb:sync-analytics {--days=7} {--store=}';
    protected $description = 'Sync sales funnel from WB';

    public function handle()
    {
        $days = (int) $this->option('days');
        $days = min(7, $days);
        $dateFrom = Carbon::now()->subDays($days)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        $stores = Store::whereNotNull('api_key_standard')->when($this->option('store'), fn ($query, $storeId) => $query->whereKey($storeId))->get();

        foreach ($stores as $store) {
            $this->info("Syncing analytics for store: {$store->name}");
            $nmIds = Product::where('store_id', $store->id)->pluck('nm_id')->toArray();
            if (empty($nmIds)) continue;

            $chunks = array_chunk($nmIds, 20);

            foreach ($chunks as $chunk) {
                $response = null;
                $lastError = null;

                for ($attempt = 1; $attempt <= 5; $attempt++) {
                    try {
                        $response = Http::withHeaders([
                            'Authorization' => $store->api_key_standard,
                            'Accept' => 'application/json',
                        ])->connectTimeout(15)->timeout(90)->post('https://seller-analytics-api.wildberries.ru/api/analytics/v3/sales-funnel/products/history', [
                            'selectedPeriod' => ['start' => $dateFrom, 'end' => $dateTo],
                            'nmIds' => $chunk,
                            'aggregationLevel' => 'day',
                            'skipDeletedNm' => false,
                        ]);
                        if ($response->successful()) break;

                        $lastError = "HTTP {$response->status()}: ".mb_substr($response->body(), 0, 1000);
                        if ($response->status() !== 429 && $response->status() < 500) break;
                    } catch (\Illuminate\Http\Client\ConnectionException $exception) {
                        $lastError = $exception->getMessage();
                    }

                    if ($attempt < 5) {
                        $delay = $response?->status() === 429
                            ? max(22, (int) $response->header('Retry-After'))
                            : min(30, $attempt * 5);
                        $this->warn("Analytics request attempt {$attempt} failed; retrying in {$delay}s.");
                        sleep($delay);
                    }
                }

                if (!$response?->successful()) {
                    $this->error("Failed to fetch analytics after retries: ".($lastError ?? 'No response'));
                    return self::FAILURE;
                }

                $data = $response->json();
                $cards = $data['data']['cards'] ?? $data['data'] ?? $data ?? [];

                $upsertData = [];
                foreach ($cards as $card) {
                    $productInfo = $card['product'] ?? [];
                    $history = $card['history'] ?? [];
                    
                    $nmId = $productInfo['nmId'] ?? null;
                    if (!$nmId) continue;

                    foreach ($history as $stat) {
                        $orderCount = $stat['orderCount'] ?? 0;
                        $orderSum = $stat['orderSum'] ?? 0;
                        $avgPrice = $orderCount > 0 ? $orderSum / $orderCount : 0;

                        $upsertData[] = [
                            'store_id' => $store->id,
                            'nm_id' => $nmId,
                            'date' => date('Y-m-d', strtotime($stat['date'])),
                            'vendor_code' => $productInfo['vendorCode'] ?? null,
                            'brand_name' => $productInfo['brandName'] ?? null,
                            'object_id' => $productInfo['subjectId'] ?? null,
                            'object_name' => $productInfo['subjectName'] ?? null,
                            'open_card_count' => $stat['openCount'] ?? 0,
                            'add_to_cart_count' => $stat['cartCount'] ?? 0,
                            'orders_count' => $orderCount,
                            'buyouts_count' => $stat['buyoutCount'] ?? 0,
                            'cancel_count' => $stat['cancelCount'] ?? 0,
                            'orders_sum_rub' => $orderSum,
                            'buyouts_sum_rub' => $stat['buyoutSum'] ?? 0,
                            'cancel_sum_rub' => $stat['cancelSum'] ?? 0,
                            'avg_price_rub' => $avgPrice,
                            'avg_orders_count_per_day' => 0,
                            'conversion_open_to_cart_percent' => $stat['addToCartConversion'] ?? 0,
                            'conversion_cart_to_order_percent' => $stat['cartToOrderConversion'] ?? 0,
                            'conversion_buyouts_percent' => $stat['buyoutPercent'] ?? 0,
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }

                if (!empty($upsertData)) {
                    ProductAnalytic::upsert(
                        $upsertData,
                        ['store_id', 'nm_id', 'date'],
                        ['vendor_code', 'brand_name', 'object_id', 'object_name', 'open_card_count', 'add_to_cart_count', 'orders_count', 'buyouts_count', 'cancel_count', 'orders_sum_rub', 'buyouts_sum_rub', 'cancel_sum_rub', 'avg_price_rub', 'conversion_open_to_cart_percent', 'conversion_cart_to_order_percent', 'conversion_buyouts_percent', 'updated_at']
                    );
                }

                sleep(21);
            }
        }

        $this->info("Analytics sync completed.");
    }
}
