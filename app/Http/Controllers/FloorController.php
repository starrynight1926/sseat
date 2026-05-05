<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Shop;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function edit(Shop $shop, Floor $floor)
    {
        abort_unless($floor->shop_id === $shop->id, 404);
        return view('editor', [
            'shop' => $shop,
            'floor' => $floor,
            'floors' => $shop->floors,
        ]);
    }

    public function view(Shop $shop, Floor $floor)
    {
        abort_unless($floor->shop_id === $shop->id, 404);
        return view('viewer', [
            'shop' => $shop,
            'floor' => $floor,
            'floors' => $shop->floors,
        ]);
    }

    public function store(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
        ]);
        $order = ($shop->floors()->max('order') ?? 0) + 1;
        $floor = $shop->floors()->create([
            'name' => $data['name'],
            'order' => $order,
        ]);
        return response()->json(['success' => true, 'data' => $floor]);
    }

    public function update(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'name'   => 'sometimes|string|max:120',
            'layout' => 'sometimes|array',
            'bg_url' => 'sometimes|nullable|string',
            'order'  => 'sometimes|integer',
        ]);
        $floor->update($data);
        return response()->json(['success' => true, 'data' => $floor]);
    }

    public function destroy(Floor $floor)
    {
        $floor->delete();
        return response()->json(['success' => true]);
    }
}
