<?php

namespace App\Http\Controllers;

use App\Models\Chair;
use App\Models\Floor;
use Illuminate\Http\Request;

class ChairController extends Controller
{
    /**
     * Toggle a chair's status. Owner-only (cashier/manager scenario).
     */
    public function toggle(Request $request, Chair $chair)
    {
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            abort_unless($chair->floor->shop->user_id === $user->id, 403);
        }
        $next = $request->input('status');
        $allowed = [Chair::STATUS_AVAILABLE, Chair::STATUS_OCCUPIED];
        if (!in_array($next, $allowed, true)) {
            // No explicit status → flip
            $next = $chair->status === Chair::STATUS_OCCUPIED
                ? Chair::STATUS_AVAILABLE
                : Chair::STATUS_OCCUPIED;
        }
        $chair->status = $next;
        $chair->save();

        try {
            broadcast(new \App\Events\ChairStatusUpdated($chair->floor_id, $chair->external_id, $chair->status));
        } catch (\Throwable $e) {
            \Log::warning('[broadcast] ChairStatusUpdated failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chair->id,
                'external_id' => $chair->external_id,
                'status' => $chair->status,
            ],
        ]);
    }

    /**
     * Public endpoint: return current chair statuses for a floor (polling).
     */
    public function statuses(Floor $floor)
    {
        $chairs = $floor->chairs()->get(['external_id', 'status']);
        return response()->json([
            'success' => true,
            'data' => $chairs->map(fn($c) => [
                'external_id' => $c->external_id,
                'status' => $c->status,
            ]),
        ]);
    }

    /**
     * Reset all chairs of a floor — owner only.
     */
    public function resetFloor(Request $request, Floor $floor)
    {
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            abort_unless($floor->shop->user_id === $user->id, 403);
        }
        $floor->chairs()->update(['status' => Chair::STATUS_AVAILABLE]);
        return response()->json(['success' => true]);
    }
}
