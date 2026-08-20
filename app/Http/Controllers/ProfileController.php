<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['stores:id,name', 'products:id,store_id,vendor_code,title']);
        $storeIds = $user->stores->pluck('id');

        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
            'products' => Product::whereIn('store_id', $storeIds)
                ->orderBy('title')
                ->get(['id', 'store_id', 'vendor_code', 'title']),
            'selectedProductIds' => $user->products->pluck('id'),
            'stores' => $user->stores,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateProducts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $productIds = $validated['product_ids'] ?? [];
        $allowedIds = Product::whereIn('store_id', $request->user()->stores()->pluck('stores.id'))
            ->whereIn('id', $productIds)
            ->pluck('id');

        if ($allowedIds->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'product_ids' => 'Можно выбирать товары только из назначенных вам магазинов.',
            ]);
        }

        $request->user()->products()->sync($allowedIds);

        return Redirect::route('profile.edit')->with('status', 'products-updated');
    }
}
