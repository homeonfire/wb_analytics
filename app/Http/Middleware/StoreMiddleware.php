<?php

namespace App\Http\Middleware;

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
        
        if ($storeId) {
            $store = $user->stores()->find($storeId);
        }

        if (!$store) {
            $store = $user->stores()->first();
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
