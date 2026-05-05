<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Shop;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function edit(Request $request, Shop $shop, Floor $floor)
    {
        abort_unless($floor->shop_id === $shop->id, 404);
        abort_unless($shop->user_id === $request->user()->id, 403);
        return view('editor', [
            'shop' => $shop,
            'floor' => $floor,
            'floors' => $shop->floors,
        ]);
    }

    public function view(Shop $shop, Floor $floor)
    {
        return $this->renderViewer($shop, $floor, 'view');
    }

    public function operate(Request $request, Shop $shop, Floor $floor)
    {
        abort_unless($shop->user_id === $request->user()->id, 403);
        return $this->renderViewer($shop, $floor, 'operate');
    }

    private function renderViewer(Shop $shop, Floor $floor, string $mode)
    {
        abort_unless($floor->shop_id === $shop->id, 404);
        // Self-heal: if chairs row count is out of sync (e.g. legacy data), rebuild.
        if ($floor->chairs()->count() === 0 && is_array($floor->layout['objects'] ?? null)) {
            $floor->syncChairsFromLayout();
        }
        $chairs = $floor->chairs()->get(['id', 'external_id', 'status']);
        return view('viewer', [
            'shop' => $shop,
            'floor' => $floor,
            'floors' => $shop->floors,
            'chairs' => $chairs,
            'mode' => $mode,
        ]);
    }

    public function store(Request $request, Shop $shop)
    {
        abort_unless($shop->user_id === $request->user()->id, 403);
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
        abort_unless($floor->shop->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'name'   => 'sometimes|string|max:120',
            'layout' => 'sometimes|array',
            'bg_url' => 'sometimes|nullable|string',
            'order'  => 'sometimes|integer',
        ]);
        $floor->update($data);
        if (array_key_exists('layout', $data)) {
            $floor->refresh();
            $floor->syncChairsFromLayout();
        }
        return response()->json(['success' => true, 'data' => $floor]);
    }

    public function destroy(Request $request, Floor $floor)
    {
        abort_unless($floor->shop->user_id === $request->user()->id, 403);
        $floor->delete();
        return response()->json(['success' => true]);
    }
}
