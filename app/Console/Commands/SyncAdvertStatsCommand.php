<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\AdvertCampaign;
use App\Models\AdvertStatistic;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncAdvertStatsCommand extends Command
{
    protected $signature = 'wb:sync-advert-stats {--days=3}';
    protected $description = 'Sync advert statistics from WB';

    public function handle()
    {
        $days = (int) $this->option('days');
        
        $stores = Store::whereNotNull('api_key_advert')->get();

        foreach ($stores as $store) {
            $this->info("Syncing advert stats for store: {$store->name}");
            
            $campaigns = AdvertCampaign::where('store_id', $store->id)
                ->where('status', 9)
                ->get();
                
            if ($campaigns->isEmpty()) continue;
            
            foreach ($campaigns as $campaign) {
                $response = Http::withHeaders([
                    'Authorization' => $store->api_key_advert,
                    'Accept' => 'application/json'
                ])->get('https://advert-api.wildberries.ru/adv/v3/fullstats', [
                    'id' => $campaign->advert_id
                ]);

                if (!$response->successful()) {
                    $this->error("Failed to fetch stats for campaign " . $campaign->advert_id);
                    continue;
                }

                $stats = $response->json();
                $daysData = $stats['days'] ?? $stats ?? [];
                
                $upsertData = [];
                $limitDate = Carbon::now()->subDays($days)->startOfDay();

                foreach ($daysData as $day) {
                    $dateStr = is_array($day) ? ($day['date'] ?? null) : null;
                    if (!$dateStr) continue;

                    $date = Carbon::parse($dateStr);
                    if ($date->lessThan($limitDate)) continue;

                    $upsertData[] = [
                        'advert_campaign_id' => $campaign->id,
                        'date' => $date->format('Y-m-d'),
                        'views' => $day['views'] ?? 0,
                        'clicks' => $day['clicks'] ?? 0,
                        'ctr' => $day['ctr'] ?? 0,
                        'cpc' => $day['cpc'] ?? 0,
                        'spend' => $day['sum'] ?? $day['spend'] ?? 0,
                        'atbs' => $day['atbs'] ?? 0,
                        'orders' => $day['orders'] ?? 0,
                        'cr' => $day['cr'] ?? 0,
                        'shks' => $day['shks'] ?? 0,
                        'sum_price' => $day['sum_price'] ?? 0,
                        'updated_at' => Carbon::now(),
                    ];
                }

                if (!empty($upsertData)) {
                    AdvertStatistic::upsert(
                        $upsertData,
                        ['advert_campaign_id', 'date'],
                        ['views', 'clicks', 'ctr', 'cpc', 'spend', 'atbs', 'orders', 'cr', 'shks', 'sum_price', 'updated_at']
                    );
                }

                sleep(21);
            }
        }
        $this->info("Advert stats sync completed.");
    }
}
