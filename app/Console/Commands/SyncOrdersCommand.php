<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\OrderRaw;
use App\Services\WbService;
use Carbon\Carbon;

class SyncOrdersCommand extends Command
{
    protected $signature = 'wb:sync-orders {--days=} {--store=}';
    protected $description = 'Sync orders from WB';

    public function handle()
    {
        $storeId = $this->option('store');
        $query = Store::whereNotNull('api_key_stat');
        if ($storeId) {
            $query->where('id', $storeId);
        }
        $stores = $query->get();

        foreach ($stores as $store) {
            $this->info("Syncing orders for store: {$store->name}");
            $wb = new WbService($store);

            $days = $this->option('days');
            if ($days) {
                $startDate = Carbon::now()->subDays($days);
            } else {
                $lastOrder = OrderRaw::where('store_id', $store->id)->orderBy('last_change_date', 'desc')->first();
                if ($lastOrder && $lastOrder->last_change_date) {
                    $startDate = Carbon::parse($lastOrder->last_change_date)->subMinutes(30);
                } else {
                    $startDate = Carbon::now()->subDays(30);
                }
            }

            $currentDateFrom = $startDate;

            while (true) {
                try {
                    $response = $wb->api->Statistics()->ordersFromDate($currentDateFrom->toRfc3339String());
                } catch (\Exception $e) {
                    $this->error("API Error: " . $e->getMessage());
                    break;
                }

                $orders = is_array($response) ? $response : ($response->data ?? []);
                
                if (empty($orders)) {
                    break;
                }

                $upsertData = [];
                $maxLastChangeDate = $currentDateFrom->copy();

                foreach ($orders as $order) {
                    $order = (object) $order;
                    $lastChange = Carbon::parse($order->lastChangeDate ?? $order->date);
                    if ($lastChange->greaterThan($maxLastChangeDate)) {
                        $maxLastChangeDate = $lastChange;
                    }

                    $upsertData[] = [
                        'srid' => $order->srid,
                        'store_id' => $store->id,
                        'order_date' => Carbon::parse($order->date),
                        'last_change_date' => $lastChange,
                        'nm_id' => $order->nmId,
                        'barcode' => $order->barcode,
                        'total_price' => $order->totalPrice ?? 0,
                        'discount_percent' => $order->discountPercent ?? 0,
                        'finished_price' => $order->finishedPrice ?? 0,
                        'is_cancel' => $order->isCancel ?? false,
                        'cancel_dt' => $order->cancelDate ? Carbon::parse($order->cancelDate) : null,
                        'warehouse_name' => $order->warehouseName ?? null,
                        'oblast_okrug_name' => $order->oblastOkrugName ?? null,
                        'updated_at' => Carbon::now(),
                    ];
                }

                if (!empty($upsertData)) {
                    $chunks = array_chunk($upsertData, 1000);
                    foreach ($chunks as $chunk) {
                        OrderRaw::upsert(
                            $chunk,
                            ['srid'],
                            ['last_change_date', 'total_price', 'discount_percent', 'finished_price', 'is_cancel', 'cancel_dt', 'updated_at', 'warehouse_name', 'oblast_okrug_name']
                        );
                    }
                }

                if ($maxLastChangeDate->lessThanOrEqualTo($currentDateFrom)) {
                    $currentDateFrom = $currentDateFrom->addSecond();
                } else {
                    $currentDateFrom = $maxLastChangeDate;
                }

                if (count($orders) > 2000) {
                    sleep(2);
                }
            }
        }
        $this->info("Orders sync completed.");
    }
}
