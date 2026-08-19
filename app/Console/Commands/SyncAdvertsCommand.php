<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\AdvertCampaign;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncAdvertsCommand extends Command
{
    protected $signature = 'wb:sync-adverts';
    protected $description = 'Sync advert campaigns from WB';

    public function handle()
    {
        $stores = Store::whereNotNull('api_key_advert')->get();

        foreach ($stores as $store) {
            $this->info("Syncing adverts for store: {$store->name}");
            
            $response = Http::withHeaders([
                'Authorization' => $store->api_key_advert,
                'Accept' => 'application/json'
            ])->get('https://advert-api.wildberries.ru/adv/v1/promotion/count');

            if (!$response->successful()) {
                $this->error("Failed to fetch campaigns count");
                continue;
            }

            $adverts = $response->json()['adverts'] ?? [];
            $campaignTypes = [];
            $allIds = [];

            foreach ($adverts as $group) {
                $type = $group['type'] ?? 0;
                $list = $group['advert_list'] ?? [];
                foreach ($list as $adv) {
                    $advId = $adv['advertId'];
                    $campaignTypes[$advId] = $type;
                    $allIds[] = $advId;
                }
            }

            $chunks = array_chunk($allIds, 50);

            foreach ($chunks as $chunk) {
                $idsParam = implode(',', $chunk);
                $detailResponse = Http::withHeaders([
                    'Authorization' => $store->api_key_advert,
                    'Accept' => 'application/json'
                ])->get('https://advert-api.wildberries.ru/api/advert/v2/adverts', [
                    'ids' => $idsParam
                ]);

                if (!$detailResponse->successful()) {
                    $this->error("Failed to fetch details for chunk");
                    continue;
                }

                $detailAdverts = $detailResponse->json()['adverts'] ?? [];

                foreach ($detailAdverts as $adv) {
                    $advId = $adv['id'] ?? null;
                    if (!$advId) continue;

                    $nmId = $this->extractNmId($adv);

                    DB::transaction(function () use ($store, $advId, $adv, $campaignTypes, $nmId) {
                        AdvertCampaign::updateOrCreate(
                            ['store_id' => $store->id, 'advert_id' => $advId],
                            [
                                'name' => $adv['settings']['name'] ?? 'Без названия',
                                'type' => $campaignTypes[$advId] ?? 0,
                                'status' => $adv['status'] ?? 0,
                                'daily_budget' => $adv['dailyBudget'] ?? 0,
                                'create_time' => isset($adv['timestamps']['created']) ? Carbon::parse($adv['timestamps']['created']) : null,
                                'change_time' => isset($adv['timestamps']['updated']) ? Carbon::parse($adv['timestamps']['updated']) : null,
                                'nm_id' => $nmId,
                                'raw_data' => $adv
                            ]
                        );
                    });
                }
                usleep(250000); // 250ms
            }
        }
        $this->info("Adverts sync completed.");
    }

    private function extractNmId($adv)
    {
        if (isset($adv['nm_settings'][0]['nm_id'])) {
            return $adv['nm_settings'][0]['nm_id'];
        }
        if (isset($adv['unitedParams'][0]['nms'][0])) {
            return $adv['unitedParams'][0]['nms'][0];
        }
        if (isset($adv['unitedParams'][0]['menus'][0]['nms'][0])) {
            return $adv['unitedParams'][0]['menus'][0]['nms'][0];
        }
        if (isset($adv['auction_multibids'][0]['nm'])) {
            return $adv['auction_multibids'][0]['nm'];
        }
        if (isset($adv['params'][0]['nms'][0])) {
            return $adv['params'][0]['nms'][0];
        }
        if (isset($adv['params'][0]['nmId'])) {
            return $adv['params'][0]['nmId'];
        }
        return null;
    }
}
