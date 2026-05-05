<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::withCount('floors')->latest()->get();
        return view('shops.index', compact('shops'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
        ]);
        $shop = Shop::create($data);
        // Auto-create first floor
        $shop->floors()->create(['name' => 'Tầng 1', 'order' => 1]);
        return redirect()->route('shops.show', $shop);
    }

    public function show(Shop $shop)
    {
        $shop->load('floors');
        return view('shops.show', compact('shop'));
    }

    public function destroy(Shop $shop)
    {
        $shop->delete();
        return redirect()->route('shops.index');
    }
}
