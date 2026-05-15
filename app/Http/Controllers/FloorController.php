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
        $this->authorizeShopAccess($request, $shop);
        return view('editor', [
            'shop' => $shop,
            'floor' => $floor,
            'floors' => $shop->floors,
        ]);
    }

    public function view(Request $request, Shop $shop, Floor $floor)
    {
        abort_unless($floor->shop_id === $shop->id, 404);
        // Public access requires the floor to be approved.
        // Owner / admin can preview at any status.
        $user = $request->user();
        $isOwner = $user && $shop->user_id === $user->id;
        $isAdmin = $user && $user->isAdmin();
        if (!$floor->isApproved() && !$isOwner && !$isAdmin) {
            abort(403, 'Sơ đồ này chưa được duyệt công khai.');
        }
        return $this->renderViewer($shop, $floor, 'view');
    }

    public function operate(Request $request, Shop $shop, Floor $floor)
    {
        $this->authorizeShopAccess($request, $shop);
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
        $this->authorizeShopAccess($request, $shop);
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
        $this->authorizeShopAccess($request, $floor->shop);
        if ($floor->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Sơ đồ đang bị khoá bởi quản trị viên.',
            ], 423);
        }
        $data = $request->validate([
            'name'   => 'sometimes|string|max:120',
            'layout' => 'sometimes|array',
            'bg_url' => 'sometimes|nullable|string',
            'order'  => 'sometimes|integer',
        ]);
        // If the layout changes after approval, revert to draft so it must be re-reviewed.
        if (array_key_exists('layout', $data) && $floor->status === Floor::STATUS_APPROVED) {
            $data['status'] = Floor::STATUS_DRAFT;
            $data['rejection_reason'] = null;
        }
        $floor->update($data);
        if (array_key_exists('layout', $data)) {
            $floor->refresh();
            $floor->syncChairsFromLayout();
        }
        return response()->json(['success' => true, 'data' => $floor]);
    }

    /**
     * Owner submits the floor for admin review.
     */
    public function submit(Request $request, Floor $floor)
    {
        $this->authorizeShopAccess($request, $floor->shop);
        if ($floor->is_locked) {
            return back()->withErrors(['floor' => 'Sơ đồ đang bị khoá.']);
        }
        if (!in_array($floor->status, [Floor::STATUS_DRAFT, Floor::STATUS_REJECTED], true)) {
            return back()->withErrors(['floor' => 'Sơ đồ không ở trạng thái cho phép gửi duyệt.']);
        }
        $floor->update([
            'status'           => Floor::STATUS_PENDING,
            'submitted_at'     => now(),
            'rejection_reason' => null,
        ]);
        return back()->with('status', "Đã gửi sơ đồ \"{$floor->name}\" chờ duyệt.");
    }

    public function destroy(Request $request, Floor $floor)
    {
        $this->authorizeShopAccess($request, $floor->shop);
        $floor->delete();
        return response()->json(['success' => true]);
    }

    private function authorizeShopAccess(Request $request, Shop $shop): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) return;
        abort_unless($shop->user_id === $user->id, 403);
    }
}
