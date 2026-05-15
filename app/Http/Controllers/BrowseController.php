<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Shop;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    /**
     * Trang dành cho khách: list các shop có ít nhất 1 floor đã được duyệt.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Shop::query()
            ->whereHas('floors', fn($f) => $f->where('status', Floor::STATUS_APPROVED))
            ->withCount(['floors as approved_floors_count' => fn($f) => $f->where('status', Floor::STATUS_APPROVED)])
            ->with(['floors' => fn($f) => $f->where('status', Floor::STATUS_APPROVED)->orderBy('order')])
            ->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            });
        }
        return view('browse', [
            'shops' => $query->paginate(12)->withQueryString(),
            'q' => $q,
        ]);
    }
}
