<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\SaleRaw;
use App\Services\WbService;
use Carbon\Carbon;

class SyncSalesCommand extends Command
{
    protected $signature = 'wb:sync-sales {--days=} {--store=}';
    protected $description = 'Sync sales from WB';

    public function handle()
    {
        $storeId = $this->option('store');
        $query = Store::whereNotNull('api_key_stat');
        if ($storeId) {
            $query->where('id', $storeId);
        }
        $stores = $query->get();

        foreach ($stores as $store) {
            $this->info("Syncing sales for store: {$store->name}");
            $wb = new WbService($store);

            $days = $this->option('days');
            if ($days) {
                $startDate = Carbon::now()->subDays($days);
            } else {
                $lastSale = SaleRaw::where('store_id', $store->id)->orderBy('last_change_date', 'desc')->first();
                if ($lastSale && $lastSale->last_change_date) {
                    $startDate = Carbon::parse($lastSale->last_change_date)->subMinutes(30);
                } else {
                    $startDate = Carbon::now()->subDays(30);
                }
            }

            $currentDateFrom = $startDate;

            while (true) {
                try {
                    $response = retry(3, fn () => $wb->api->Statistics()->salesFromDate($currentDateFrom), 3000);
                } catch (\Exception $e) {
                    $this->error("API Error: " . $e->getMessage());
                    return self::FAILURE;
                }

                $sales = is_array($response) ? $response : ($response->data ?? []);
                
                if (empty($sales)) {
                    break;
                }

                $upsertData = [];
                $maxLastChangeDate = $currentDateFrom->copy();

                foreach ($sales as $sale) {
                    $sale = (object) $sale;
                    if (empty($sale->saleID)) continue;

                    $lastChange = Carbon::parse($sale->lastChangeDate ?? $sale->date);
                    if ($lastChange->greaterThan($maxLastChangeDate)) {
                        $maxLastChangeDate = $lastChange;
                    }

                    $upsertData[] = [
                        'sale_id' => $sale->saleID,
                        'store_id' => $store->id,
                        'sale_date' => Carbon::parse($sale->date),
                        'last_change_date' => $lastChange,
                        'nm_id' => $sale->nmId,
                        'barcode' => $sale->barcode,
                        'total_price' => $sale->totalPrice ?? 0,
                        'discount_percent' => $sale->discountPercent ?? 0,
                        'price_with_disc' => $sale->priceWithDisc ?? 0,
                        'for_pay' => $sale->forPay ?? 0,
                        'finished_price' => $sale->finishedPrice ?? 0,
                        'warehouse_name' => $sale->warehouseName ?? null,
                        'region_name' => $sale->regionName ?? null,
                        'updated_at' => Carbon::now(),
                    ];
                }

                if (!empty($upsertData)) {
                    $chunks = array_chunk($upsertData, 1000);
                    foreach ($chunks as $chunk) {
                        SaleRaw::upsert(
                            $chunk,
                            ['sale_id'],
                            ['store_id', 'sale_date', 'last_change_date', 'nm_id', 'barcode', 'total_price', 'discount_percent', 'price_with_disc', 'for_pay', 'finished_price', 'warehouse_name', 'region_name', 'updated_at']
                        );
                    }
                }

                break;
            }
        }
        $this->info("Sales sync completed.");
    }
}
