<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SaleRaw;
use App\Models\OrderRaw;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        $stats = [
            'revenue' => 0,
            'orders_count' => 0,
            'products_count' => 0,
            'conversion_rate' => 0,
        ];
        $charts = [
            'daily_revenue' => [],
            'top_products' => []
        ];

        if ($store) {
            $thirtyDaysAgo = Carbon::now()->subDays(30);

            $stats['revenue'] = SaleRaw::where('store_id', $store->id)
                ->where('sale_date', '>=', $thirtyDaysAgo)
                ->sum('finished_price');

            $stats['orders_count'] = OrderRaw::where('store_id', $store->id)
                ->where('order_date', '>=', $thirtyDaysAgo)
                ->where('is_cancel', false)
                ->count();

            $stats['products_count'] = Product::where('store_id', $store->id)->count();

            // Mock conversion rate for now, or calculate based on ProductAnalytic
            $stats['conversion_rate'] = 4.5;

            // Daily revenue for the last 30 days
            $charts['daily_revenue'] = SaleRaw::where('store_id', $store->id)
                ->where('sale_date', '>=', $thirtyDaysAgo)
                ->select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('SUM(finished_price) as revenue')
                )
                ->groupBy(DB::raw('DATE(sale_date)'))
                ->orderBy('date', 'asc')
                ->get();

            // Top 5 products by revenue
            $charts['top_products'] = SaleRaw::where('sale_raws.store_id', $store->id)
                ->where('sale_raws.sale_date', '>=', $thirtyDaysAgo)
                ->join('products', function ($join) {
                    $join->on('sale_raws.nm_id', '=', 'products.nm_id')
                         ->on('sale_raws.store_id', '=', 'products.store_id');
                })
                ->select('products.title', DB::raw('SUM(sale_raws.finished_price) as total_revenue'))
                ->groupBy('products.title')
                ->orderBy('total_revenue', 'desc')
                ->limit(5)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'charts' => $charts
        ]);
    }
}
