<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ManagerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        // Получаем всех пользователей (менеджеров) с загрузкой их товаров (только ID)
        $managers = User::with(['products:id,vendor_code,title', 'stores:id,name'])
            ->withCount(['products', 'stores'])
            ->where('is_super_admin', false)
            ->orderBy('id', 'desc')
            ->get();
            
        // Все доступные товары для привязки (фильтруем по текущему магазину если нужно)
        $storeId = $request->session()->get('current_store_id');
        $products = Product::when($storeId, function($q) use ($storeId) {
                return $q->where('store_id', $storeId);
            })
            ->orderBy('title')
            ->get(['id', 'vendor_code', 'title']);

        return Inertia::render('Managers/Index', [
            'managers' => $managers,
            'products' => $products,
            'stores' => Store::orderBy('name')->get(['id', 'name'])
        ]);
    }

    public function store(Request $request)
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Менеджер успешно добавлен.');
    }

    public function show(Request $request, User $manager)
    {
        abort_unless((bool) $request->user()?->is_super_admin && !$manager->is_super_admin, 403);

        $manager->load(['stores:id,name', 'products:id,store_id,vendor_code,title']);
        $storeIds = $manager->stores->pluck('id');

        return Inertia::render('Managers/Show', [
            'manager' => $manager,
            'stores' => Store::orderBy('name')->get(['id', 'name']),
            'products' => Product::whereIn('store_id', $storeIds)
                ->orderBy('title')
                ->get(['id', 'store_id', 'vendor_code', 'title']),
        ]);
    }

    public function bindProducts(Request $request, User $manager)
    {
        abort_unless((bool) $request->user()?->is_super_admin && !$manager->is_super_admin, 403);
        $validated = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $productIds = $validated['product_ids'] ?? [];
        $allowedIds = Product::whereIn('store_id', $manager->stores()->pluck('stores.id'))
            ->whereIn('id', $productIds)
            ->pluck('id');

        if ($allowedIds->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages(['product_ids' => 'Можно привязывать товары только из назначенных менеджеру магазинов.']);
        }

        $manager->products()->sync($allowedIds);

        return redirect()->back()->with('success', 'Товары успешно привязаны к менеджеру.');
    }
    public function updatePermissions(Request $request, User $manager)
    {
        abort_unless((bool) $request->user()?->is_super_admin && !$manager->is_super_admin, 403);

        $validated = $request->validate([
            'can_run_sync' => ['required', 'boolean'],
        ]);

        $manager->forceFill([
            'can_run_sync' => $validated['can_run_sync'],
        ])->save();

        return back()->with('success', 'Права менеджера обновлены.');
    }

    public function bindStores(Request $request, User $manager)
    {
        abort_unless((bool) $request->user()?->is_super_admin && !$manager->is_super_admin, 403);

        $validated = $request->validate([
            'store_ids' => ['array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
        ]);

        $storeIds = $validated['store_ids'] ?? [];
        $manager->stores()->sync($storeIds);

        $productsOutsideStores = $manager->products()->whereNotIn('products.store_id', $storeIds)->pluck('products.id');
        if ($productsOutsideStores->isNotEmpty()) {
            $manager->products()->detach($productsOutsideStores);
        }

        return back()->with('success', 'Магазины менеджера обновлены.');
    }
}
