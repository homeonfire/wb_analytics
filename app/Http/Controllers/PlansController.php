<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\ProductPlan;
use Carbon\Carbon;

class PlansController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->is_super_admin && !auth()->user()->can_manage_plans) {
            abort(403, 'Unauthorized action.');
        }

        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        // Selected month/year. Default to current month.
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $products = Product::where('store_id', $store->id)
            ->with(['plans' => function($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            }])
            ->get();
            
        // Map products with their current plan if it exists
        $productsData = $products->map(function($product) {
            $plan = $product->plans->first();
            return [
                'id' => $product->id,
                'title' => $product->title,
                'vendor_code' => $product->vendor_code,
                'main_image_url' => $product->main_image_url,
                'orders_plan' => $plan ? $plan->orders_plan : 0,
                'sales_plan' => $plan ? $plan->sales_plan : 0,
            ];
        });

        return Inertia::render('Plans/Index', [
            'products' => $productsData,
            'selectedMonth' => (int) $month,
            'selectedYear' => (int) $year,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->is_super_admin && !auth()->user()->can_manage_plans) {
            abort(403, 'Unauthorized action.');
        }

        $store = app('current_store') ?? null;
        abort_unless($store, 403);

        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'plans' => 'required|array',
            'plans.*.product_id' => 'required|exists:products,id',
            'plans.*.orders_plan' => 'required|numeric|min:0',
            'plans.*.sales_plan' => 'required|numeric|min:0',
        ]);

        $productIds = collect($request->plans)->pluck('product_id')->unique();
        abort_unless(
            Product::where('store_id', $store->id)->whereIn('id', $productIds)->count() === $productIds->count(),
            403,
        );

        $year = $request->year;
        $month = $request->month;

        foreach ($request->plans as $planData) {
            ProductPlan::updateOrCreate(
                [
                    'product_id' => $planData['product_id'],
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'orders_plan' => $planData['orders_plan'],
                    'sales_plan' => $planData['sales_plan'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Планы успешно сохранены');
    }
}
