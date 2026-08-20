<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $query = Product::with('skus')
            ->where('store_id', $store->id)
            ->withCount(['orderRaws as orders_30d' => function($q) {
                $q->where('order_date', '>=', now()->subDays(30));
            }])
            ->withSum(['saleRaws as revenue_30d' => function($q) {
                $q->where('sale_date', '>=', now()->subDays(30));
            }], 'finished_price');

        $sortBy = $request->input('sort', 'orders_30d');
        $sortDir = $request->input('dir', 'desc');

        $allowedSorts = ['revenue_30d', 'orders_30d', 'title', 'vendor_code', 'margin_30d'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('orders_30d', 'desc');
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('vendor_code', 'ilike', "%{$search}%")
                  ->orWhere('nm_id', 'ilike', "%{$search}%");
            });
        }

        $products = $query->paginate(20)->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'sort', 'dir'])
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $store = app('current_store') ?? null;
        if (!$store || $product->store_id !== $store->id) {
            abort(403);
        }

        $product->load(['skus.warehouseStocks', 'warehouseStocks']);
        
        // Also fetch analytics for funnel
        $analytics = \App\Models\ProductAnalytic::where('nm_id', $product->nm_id)
            ->orderBy('date', 'asc')
            ->get();

        // Fetch Fact Orders
        $orders_fact = \App\Models\OrderRaw::where('store_id', $store->id)
            ->where('nm_id', $product->nm_id)
            ->selectRaw('DATE(order_date) as date, count(*) as orders_count_fact, sum(finished_price) as order_sum')
            ->groupByRaw('DATE(order_date)')
            ->get();

        // Fetch Fact Sales
        $sales_fact = \App\Models\SaleRaw::where('store_id', $store->id)
            ->where('nm_id', $product->nm_id)
            ->selectRaw('DATE(sale_date) as date, count(*) as buyouts_count_fact, sum(finished_price) as revenue, sum(for_pay) as for_pay_sum')
            ->groupByRaw('DATE(sale_date)')
            ->get();

        $periods = collect([now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])
            ->map(function (Carbon $period) use ($product, $store) {
                $end = $period->copy()->endOfMonth();
                $plan = ProductPlan::where('product_id', $product->id)
                    ->where('year', $period->year)
                    ->where('month', $period->month)
                    ->first();
                $ordersFact = \App\Models\OrderRaw::where('store_id', $store->id)
                    ->where('nm_id', $product->nm_id)
                    ->whereBetween('order_date', [$period, $end])
                    ->count();
                $salesFact = \App\Models\SaleRaw::where('store_id', $store->id)
                    ->where('nm_id', $product->nm_id)
                    ->whereBetween('sale_date', [$period, $end])
                    ->count();

                return [
                    'year' => $period->year,
                    'month' => $period->month,
                    'label' => $period->copy()->locale('ru')->translatedFormat('F Y'),
                    'orders_plan' => (int) ($plan?->orders_plan ?? 0),
                    'sales_plan' => (int) ($plan?->sales_plan ?? 0),
                    'orders_fact' => $ordersFact,
                    'sales_fact' => $salesFact,
                    'has_plan' => (bool) $plan,
                ];
            })->values();

        $campaigns = \App\Models\AdvertCampaign::where('store_id', $store->id)
            ->where('nm_id', $product->nm_id)
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'analytics' => $analytics,
            'ordersFact' => $orders_fact,
            'salesFact' => $sales_fact,
            'campaigns' => $campaigns,
            'planFactPeriods' => $periods,
            'canManagePlans' => (bool) ($request->user()->is_super_admin || $request->user()->can_manage_plans),
        ]);
    }

    public function updatePlan(Request $request, Product $product)
    {
        $store = app('current_store');
        abort_unless($store && $product->store_id === $store->id, 403);
        abort_unless((bool) ($request->user()->is_super_admin || $request->user()->can_manage_plans), 403);

        $validated = $request->validate([
            'orders_plan' => ['required', 'integer', 'min:0'],
            'sales_plan' => ['required', 'integer', 'min:0'],
        ]);
        $period = now();
        ProductPlan::updateOrCreate(
            ['product_id' => $product->id, 'year' => $period->year, 'month' => $period->month],
            $validated,
        );

        Cache::put("analytics:store:{$store->id}:version", now()->getTimestampMs(), now()->addYear());

        return back()->with('success', 'План на текущий месяц сохранён.');
    }
}
