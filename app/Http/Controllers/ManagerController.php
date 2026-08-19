<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ManagerController extends Controller
{
    public function index(Request $request)
    {
        // Получаем всех пользователей (менеджеров) с загрузкой их товаров (только ID)
        $managers = User::with('products:id,vendor_code,title')
            ->withCount('products')
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
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
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

    public function bindProducts(Request $request, User $manager)
    {
        $validated = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $manager->products()->sync($validated['product_ids'] ?? []);

        return redirect()->back()->with('success', 'Товары успешно привязаны к менеджеру.');
    }
}
