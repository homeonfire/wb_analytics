<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AdvertCampaign;
use App\Models\ExternalAdvert;

class AdvertController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $campaigns = AdvertCampaign::with(['product'])
            ->where('store_id', $store->id)
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'campaigns_page');

        $externalAdverts = ExternalAdvert::with(['product'])
            ->where('store_id', $store->id)
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'ext_page');

        $products = \App\Models\Product::where('store_id', $store->id)
            ->select('id', 'nm_id', 'title')
            ->orderBy('title')
            ->get();

        return Inertia::render('Adverts/Index', [
            'campaigns' => $campaigns,
            'externalAdverts' => $externalAdverts,
            'products' => $products,
        ]);
    }

    public function storeExternal(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'blogger_link' => 'required|string',
            'ad_cost' => 'required|numeric|min:0',
            'platform' => 'required|string',
            'release_date' => 'required|date',
            'formats' => 'nullable|array',
            'status' => 'required|string',
        ]);

        ExternalAdvert::create([
            'store_id' => $store->id,
            'product_id' => $validated['product_id'],
            'blogger_link' => $validated['blogger_link'],
            'ad_cost' => $validated['ad_cost'],
            'ad_spent' => $validated['ad_cost'], // assuming cost is spent immediately
            'platform' => $validated['platform'],
            'release_date' => \Carbon\Carbon::parse($validated['release_date']),
            'formats' => $validated['formats'] ?? [],
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Реклама успешно добавлена!');
    }
}
