<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        $stores = Store::orderBy('id', 'desc')->get();
        return Inertia::render('Stores/Index', [
            'stores' => $stores
        ]);
    }

    public function store(Request $request)
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key_standard' => 'nullable|string',
            'api_key_stat' => 'nullable|string',
            'api_key_advert' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        $store = Store::create($validated);

        // Привязываем новый магазин к текущему пользователю
        $request->user()->stores()->attach($store->id);

        return redirect()->back()->with('success', 'Магазин успешно добавлен.');
    }

    public function destroy(Store $store)
    {
        abort_unless((bool) request()->user()?->is_super_admin, 403);
        // Возможно, стоит отвязывать товары или удалять каскадно
        // Для простоты пока просто удаляем магазин (товары могут остаться сиротами или удалятся каскадно, если настроен foreign key CASCADE)
        $store->delete();
        
        return redirect()->back()->with('success', 'Магазин успешно удален.');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $storeId = $request->store_id;
        $user = $request->user();

        if ($user->stores()->where('stores.id', $storeId)->exists()) {
            session(['current_store_id' => $storeId]);
        }

        return redirect()->back();
    }
}
