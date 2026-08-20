<?php

namespace App\Http\Middleware;

use App\Models\Store;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $storeId = session('current_store_id');
        $store = null;
        $availableStores = fn () => $user->is_super_admin ? Store::query() : $user->stores();

        if ($storeId) {
            $store = $availableStores()->find($storeId);
        }

        if (!$store) {
            $store = $availableStores()->first();
            if ($store) {
                session(['current_store_id' => $store->id]);
            }
        }

        if ($store) {
            app()->instance('current_store', $store);
        }

        return $next($request);
    }
}
