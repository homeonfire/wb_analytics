<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\OrderRaw;
use App\Models\SaleRaw;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $store = app('current_store') ?? null;
        if (!$store) {
            return redirect()->route('dashboard')->with('error', 'Please select a store first');
        }

        $type = $request->input('type', 'orders');
        $search = $request->input('search');

        $data = null;

        if ($type === 'sales') {
            $query = SaleRaw::where('store_id', $store->id)->orderBy('sale_date', 'desc');
            
            if ($search) {
                $query->where('sale_id', 'ilike', "%{$search}%")
                      ->orWhere('barcode', 'ilike', "%{$search}%")
                      ->orWhere('nm_id', 'like', "%{$search}%");
            }
            
            $data = $query->paginate(30)->withQueryString();
        } else {
            $query = OrderRaw::where('store_id', $store->id)->orderBy('order_date', 'desc');
            
            if ($search) {
                $query->where('srid', 'ilike', "%{$search}%")
                      ->orWhere('barcode', 'ilike', "%{$search}%")
                      ->orWhere('nm_id', 'like', "%{$search}%");
            }
            
            $data = $query->paginate(30)->withQueryString();
        }

        return Inertia::render('Orders/Index', [
            'data' => $data,
            'type' => $type,
            'filters' => $request->only('search', 'type')
        ]);
    }
}
