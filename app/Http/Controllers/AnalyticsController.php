<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProductAnalytic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $days = (int) $request->input('days', 30);
        $periodStart = Carbon::now()->subDays($days);
        
        $prevPeriodStart = Carbon::now()->subDays($days * 2);
        $prevPeriodEnd = Carbon::now()->subDays($days);

        // --- FUNNEL (from ProductAnalytic) ---
        $funnel = ProductAnalytic::where('store_id', $store->id)
            ->where('date', '>=', $periodStart)
            ->selectRaw('
                SUM(open_card_count) as views,
                SUM(add_to_cart_count) as carts,
                SUM(orders_count) as orders_count,
                SUM(buyouts_count) as buyouts_count
            ')
            ->first();

        // --- ACTUAL RECEIPTS (from OrderRaw and SaleRaw) ---
        $ordersSum = \App\Models\OrderRaw::where('store_id', $store->id)
            ->where('order_date', '>=', $periodStart)
            ->where('is_cancel', false)
            ->sum('finished_price');
            
        $ordersCount = \App\Models\OrderRaw::where('store_id', $store->id)
            ->where('order_date', '>=', $periodStart)
            ->where('is_cancel', false)
            ->count();
            
        $salesSum = \App\Models\SaleRaw::where('store_id', $store->id)
            ->where('sale_date', '>=', $periodStart)
            ->sum('finished_price');
            
        $salesCount = \App\Models\SaleRaw::where('store_id', $store->id)
            ->where('sale_date', '>=', $periodStart)
            ->count();

        // Previous Period (for LFL)
        $prevOrdersSum = \App\Models\OrderRaw::where('store_id', $store->id)
            ->whereBetween('order_date', [$prevPeriodStart, $prevPeriodEnd])
            ->where('is_cancel', false)
            ->sum('finished_price');
            
        $prevSalesSum = \App\Models\SaleRaw::where('store_id', $store->id)
            ->whereBetween('sale_date', [$prevPeriodStart, $prevPeriodEnd])
            ->sum('finished_price');
            
        $prevOrdersCount = \App\Models\OrderRaw::where('store_id', $store->id)
            ->whereBetween('order_date', [$prevPeriodStart, $prevPeriodEnd])
            ->where('is_cancel', false)
            ->count();

        $prevSalesCount = \App\Models\SaleRaw::where('store_id', $store->id)
            ->whereBetween('sale_date', [$prevPeriodStart, $prevPeriodEnd])
            ->count();

        $calcLfl = function($curr, $prev) {
            if ($prev == 0) return $curr > 0 ? 100 : 0;
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        $buyoutRate = $ordersCount > 0 ? round(($salesCount / $ordersCount) * 100, 2) : 0;
        $prevBuyoutRate = $prevOrdersCount > 0 ? round(($prevSalesCount / $prevOrdersCount) * 100, 2) : 0;
        
        $conversionRate = ($funnel->views > 0) ? round(($funnel->carts / $funnel->views) * 100, 2) : 0;
        $conversionOrderRate = ($funnel->carts > 0) ? round(($funnel->orders_count / $funnel->carts) * 100, 2) : 0;

        $kpis = [
            'orders_sum' => $ordersSum,
            'orders_count' => $ordersCount,
            'buyouts_sum' => $salesSum,
            'buyouts_count' => $salesCount,
            'buyout_rate' => $buyoutRate,
            'conversion_cart_rate' => $conversionRate,
            'conversion_order_rate' => $conversionOrderRate,
            
            // LFL
            'lfl_orders_sum' => $calcLfl($ordersSum, $prevOrdersSum),
            'lfl_orders_count' => $calcLfl($ordersCount, $prevOrdersCount),
            'lfl_buyouts_sum' => $calcLfl($salesSum, $prevSalesSum),
            'lfl_buyouts_count' => $calcLfl($salesCount, $prevSalesCount),
            'lfl_buyout_rate' => round($buyoutRate - $prevBuyoutRate, 2), // difference in percentage points

            // Funnel specifically uses ProductAnalytic
            'views' => $funnel->views ?? 0,
            'carts' => $funnel->carts ?? 0,
            'funnel_orders' => $funnel->orders_count ?? 0,
            'funnel_buyouts' => $funnel->buyouts_count ?? 0,
        ];

        // --- TREND CHART ---
        $ordersTrend = \App\Models\OrderRaw::where('store_id', $store->id)
            ->where('order_date', '>=', $periodStart)
            ->where('is_cancel', false)
            ->select(DB::raw('DATE(order_date) as date'), DB::raw('COUNT(id) as orders_count'))
            ->groupBy(DB::raw('DATE(order_date)'))
            ->get()->keyBy('date');

        $salesTrend = \App\Models\SaleRaw::where('store_id', $store->id)
            ->where('sale_date', '>=', $periodStart)
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('COUNT(id) as buyouts_count'))
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->get()->keyBy('date');
            
        $trendDates = $ordersTrend->keys()->merge($salesTrend->keys())->unique()->sort()->values();
        $trend = [];
        foreach ($trendDates as $date) {
            $trend[] = [
                'date' => $date,
                'orders_count' => isset($ordersTrend[$date]) ? $ordersTrend[$date]->orders_count : 0,
                'buyouts_count' => isset($salesTrend[$date]) ? $salesTrend[$date]->buyouts_count : 0,
            ];
        }

        // --- TOP PRODUCTS ---
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $salesByProduct = DB::table('sale_raws')
            ->where('sale_date', '>=', $periodStart)
            ->selectRaw("store_id, nm_id, SUM(finished_price) as revenue_30d, SUM(CASE WHEN finished_price > 0 THEN 1 ELSE 0 END) as sales_count")
            ->groupBy('store_id', 'nm_id');

        $ordersByProduct = DB::table('order_raws')
            ->where('order_date', '>=', $periodStart)
            ->where('is_cancel', false)
            ->selectRaw('store_id, nm_id, COUNT(*) as orders_count')
            ->groupBy('store_id', 'nm_id');

        $topProducts = \App\Models\Product::where('products.store_id', $store->id)
            ->joinSub($salesByProduct, 'sales_agg', function ($join) {
                $join->on('products.store_id', '=', 'sales_agg.store_id')
                    ->on('products.nm_id', '=', 'sales_agg.nm_id');
            })
            ->leftJoinSub($ordersByProduct, 'orders_agg', function ($join) {
                $join->on('products.store_id', '=', 'orders_agg.store_id')
                    ->on('products.nm_id', '=', 'orders_agg.nm_id');
            })
            ->select(
                'products.id',
                'products.title',
                'products.vendor_code',
                'products.main_image_url',
                'products.abc_class',
                DB::raw('sales_agg.revenue_30d as revenue_30d'),
                DB::raw('sales_agg.sales_count as sales_count'),
                DB::raw('COALESCE(orders_agg.orders_count, 0) as orders_count')
            )
            ->withSum('warehouseStocks', 'quantity')
            ->with(['plans' => function($q) use ($currentMonth, $currentYear) {
                $q->where('month', $currentMonth)->where('year', $currentYear);
            }])
            ->orderByDesc('sales_agg.revenue_30d')
            ->take(10)
            ->get();

        // Calculate Days of Supply & Plan-Fact for Top Products
        $topProducts->transform(function($product) use ($days) {
            $salesPerDay = $product->sales_count / $days;
            $stock = $product->warehouse_stocks_sum_quantity ?? 0;
            $daysOfSupply = $salesPerDay > 0 ? round($stock / $salesPerDay) : 999;
            
            $product->days_of_supply = $daysOfSupply;
            $product->total_stock = $stock;

            // Plan vs Fact
            $plan = $product->plans->first();
            $product->plan_orders = $plan ? $plan->orders_plan : 0;
            $product->plan_sales = $plan ? $plan->sales_plan : 0;
            
            $product->fact_orders_percent = $product->plan_orders > 0 ? min(100, round(($product->orders_count / $product->plan_orders) * 100)) : 0;
            $product->fact_sales_percent = $product->plan_sales > 0 ? min(100, round(($product->sales_count / $product->plan_sales) * 100)) : 0;

            return $product;
        });

        // --- WAREHOUSE DISTRIBUTION ---
        $warehouses = \App\Models\OrderRaw::where('store_id', $store->id)
            ->where('order_date', '>=', $periodStart)
            ->whereNotNull('warehouse_name')
            ->where('warehouse_name', '!=', '')
            ->select('warehouse_name', DB::raw('COUNT(id) as count'))
            ->groupBy('warehouse_name')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // --- ANTI-TOP CANCELLATIONS ---
        $antiTop = \App\Models\OrderRaw::where('order_raws.store_id', $store->id)
            ->where('order_raws.order_date', '>=', $periodStart)
            ->join('products', function ($join) {
                $join->on('order_raws.nm_id', '=', 'products.nm_id')
                     ->on('order_raws.store_id', '=', 'products.store_id');
            })
            ->select(
                'products.id',
                'products.title',
                'products.main_image_url',
                DB::raw('COUNT(order_raws.id) as total_orders'),
                DB::raw('SUM(CASE WHEN order_raws.is_cancel = true THEN 1 ELSE 0 END) as cancels_count')
            )
            ->groupBy('products.id', 'products.title', 'products.main_image_url')
            ->having(DB::raw('COUNT(order_raws.id)'), '>=', 5)
            ->orderByDesc(DB::raw('SUM(CASE WHEN order_raws.is_cancel = true THEN 1 ELSE 0 END) * 1.0 / NULLIF(COUNT(order_raws.id), 0)'))
            ->take(5)
            ->get();
            
        $antiTop->transform(function($item) {
            $item->cancel_rate = $item->total_orders > 0 ? round(($item->cancels_count / $item->total_orders) * 100, 1) : 0;
            return $item;
        });

        return Inertia::render('Analytics/Index', [
            'kpis' => $kpis,
            'trend' => $trend,
            'topProducts' => $topProducts,
            'warehouses' => $warehouses,
            'antiTop' => $antiTop,
            'days' => $days
        ]);
    }
}
