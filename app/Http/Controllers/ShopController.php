<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->isSuperAdmin()
            ? Shop::query()
            : Shop::where('user_id', $user->id);

        $shops = $query->withCount('floors')->latest()->get();
        return view('shops.index', compact('shops'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
        ]);
        $data['user_id'] = $request->user()->id;
        $shop = Shop::create($data);
        $shop->floors()->create(['name' => 'Tầng 1', 'order' => 1]);
        return redirect()->route('shops.show', $shop);
    }

    public function show(Request $request, Shop $shop)
    {
        $this->authorizeOwner($request, $shop);
        $shop->load('floors');
        return view('shops.show', compact('shop'));
    }

    public function destroy(Request $request, Shop $shop)
    {
        $this->authorizeOwner($request, $shop);
        $shop->delete();
        return redirect()->route('shops.index');
    }

    private function authorizeOwner(Request $request, Shop $shop): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) return;
        abort_unless($shop->user_id === $user->id, 403);
    }
}
