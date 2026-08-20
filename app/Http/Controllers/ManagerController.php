<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ManagerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return Inertia::render('Managers/Index', [
            'managers' => User::with(['products:id,vendor_code,title', 'stores:id,name'])
                ->withCount(['products', 'stores'])
                ->whereKeyNot($request->user()->id)
                ->orderByDesc('id')
                ->get(),
            'products' => Product::where('store_id', app('current_store')->id)
                ->orderBy('title')->get(['id', 'vendor_code', 'title']),
            'stores' => Store::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'is_super_admin' => ['required', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->forceFill(['is_super_admin' => $validated['is_super_admin']])->save();

        return back()->with('success', 'Пользователь успешно добавлен.');
    }

    public function show(Request $request, User $manager)
    {
        $this->authorizeTarget($request, $manager);
        $manager->load(['stores:id,name', 'products:id,store_id,vendor_code,title']);
        $storeIds = $manager->is_super_admin ? Store::pluck('id') : $manager->stores->pluck('id');

        return Inertia::render('Managers/Show', [
            'manager' => $manager,
            'stores' => Store::orderBy('name')->get(['id', 'name']),
            'products' => Product::whereIn('store_id', $storeIds)
                ->orderBy('title')->get(['id', 'store_id', 'vendor_code', 'title']),
        ]);
    }

    public function bindProducts(Request $request, User $manager)
    {
        $this->authorizeManagerTarget($request, $manager);
        $validated = $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);
        $productIds = $validated['product_ids'] ?? [];
        $allowedIds = Product::whereIn('store_id', $manager->stores()->pluck('stores.id'))
            ->whereIn('id', $productIds)->pluck('id');

        if ($allowedIds->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'product_ids' => 'Можно привязывать товары только из назначенных менеджеру магазинов.',
            ]);
        }
        $manager->products()->sync($allowedIds);

        return back()->with('success', 'Товары успешно привязаны к менеджеру.');
    }

    public function updatePermissions(Request $request, User $manager)
    {
        $this->authorizeTarget($request, $manager);
        $validated = $request->validate([
            'is_super_admin' => ['required', 'boolean'],
            'can_run_sync' => ['required', 'boolean'],
            'can_manage_plans' => ['required', 'boolean'],
        ]);
        $manager->forceFill([
            'is_super_admin' => $validated['is_super_admin'],
            'can_run_sync' => $validated['is_super_admin'] ? false : $validated['can_run_sync'],
            'can_manage_plans' => $validated['is_super_admin'] ? false : $validated['can_manage_plans'],
        ])->save();

        return back()->with('success', 'Роль и права пользователя обновлены.');
    }

    public function bindStores(Request $request, User $manager)
    {
        $this->authorizeManagerTarget($request, $manager);
        $validated = $request->validate([
            'store_ids' => ['array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
        ]);
        $storeIds = $validated['store_ids'] ?? [];
        $manager->stores()->sync($storeIds);
        $productsOutsideStores = $manager->products()
            ->whereNotIn('products.store_id', $storeIds)->pluck('products.id');
        if ($productsOutsideStores->isNotEmpty()) {
            $manager->products()->detach($productsOutsideStores);
        }

        return back()->with('success', 'Магазины менеджера обновлены.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
    }

    private function authorizeTarget(Request $request, User $target): void
    {
        $this->authorizeAdmin($request);
        abort_if($target->id === $request->user()->id, 403);
    }

    private function authorizeManagerTarget(Request $request, User $target): void
    {
        $this->authorizeTarget($request, $target);
        abort_if((bool) $target->is_super_admin, 403);
    }
}
