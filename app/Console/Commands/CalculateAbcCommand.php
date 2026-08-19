<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Product;
use App\Models\SaleRaw;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalculateAbcCommand extends Command
{
    protected $signature = 'wb:calculate-abc {--store=}';
    protected $description = 'Calculate ABC analysis for products based on 30d revenue';

    public function handle()
    {
        $stores = Store::query()->when($this->option('store'), fn ($query, $storeId) => $query->whereKey($storeId))->get();
        $dateFrom = Carbon::now()->subDays(30);

        foreach ($stores as $store) {
            $this->info("Calculating ABC for store: {$store->name}");
            
            $revenues = SaleRaw::where('store_id', $store->id)
                ->where('sale_date', '>=', $dateFrom)
                // ->where('is_cancel', false) // Note: In sales, is_cancel is usually in orders, but assuming we filter valid sales
                ->select('nm_id', DB::raw('SUM(finished_price) as revenue'))
                ->groupBy('nm_id')
                ->orderBy('revenue', 'desc')
                ->get();

            $totalRevenue = $revenues->sum('revenue');
            if ($totalRevenue <= 0) continue;

            $runningTotal = 0;
            $updates = [];

            foreach ($revenues as $rev) {
                $runningTotal += $rev->revenue;
                $percent = ($runningTotal / $totalRevenue) * 100;

                if ($percent <= 80) {
                    $class = 'A';
                } elseif ($percent <= 95) {
                    $class = 'B';
                } else {
                    $class = 'C';
                }

                $updates[] = [
                    'nm_id' => $rev->nm_id,
                    'store_id' => $store->id,
                    'abc_class' => $class,
                    'revenue_30d' => $rev->revenue
                ];
            }

            foreach ($updates as $update) {
                Product::where('nm_id', $update['nm_id'])
                    ->where('store_id', $update['store_id'])
                    ->update([
                        'abc_class' => $update['abc_class'],
                        'revenue_30d' => $update['revenue_30d']
                    ]);
            }
        }
        
        $this->info("ABC analysis completed.");
    }
}
