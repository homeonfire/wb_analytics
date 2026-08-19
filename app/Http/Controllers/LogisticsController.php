<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;

class LogisticsController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $search = $request->input('search');

        $query = Product::where('store_id', $store->id)
            ->with(['warehouseStocks', 'skus.stock', 'skus.warehouseStocks']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('vendor_code', 'ilike', "%{$search}%")
                  ->orWhere('nm_id', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(15)->withQueryString();

        return Inertia::render('Logistics/Index', [
            'products' => $products,
            'filters' => $request->only('search')
        ]);
    }
}
