<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->is_suspended) abort(403);
        if (!$user->canManageShops()) {
            // Customer → đẩy về trang duyệt sơ đồ công khai.
            return redirect()->route('browse')
                ->with('status', 'Tài khoản khách chỉ có quyền xem sơ đồ.');
        }
        return $next($request);
    }
}
