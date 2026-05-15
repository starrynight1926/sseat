<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Chair;
use App\Models\Floor;
use App\Models\Shop;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'stats' => [
                'users'     => User::count(),
                'admins'    => User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->count(),
                'suspended' => User::where('is_suspended', true)->count(),
                'shops'     => Shop::count(),
                'floors'    => Floor::count(),
                'chairs'    => Chair::count(),
                'pending'   => Floor::where('status', Floor::STATUS_PENDING)->count(),
            ],
            'recentUsers' => User::latest()->limit(5)->get(),
            'recentShops' => Shop::with('owner')->latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = $request->query('role');
        $query = User::withCount('shops')->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }
        if (in_array($role, User::ROLES, true)) {
            $query->where('role', $role);
        }
        return view('admin.users', [
            'users' => $query->paginate(20)->withQueryString(),
            'q' => $q,
            'role' => $role,
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $current = $request->user();
        // Cannot modify yourself via this form (avoid lockout)
        abort_if($user->id === $current->id, 422, 'Không thể tự sửa chính mình ở đây.');
        // Only super admin can change roles or touch other admins
        if ($user->isAdmin() || $request->filled('role')) {
            abort_unless($current->isSuperAdmin(), 403);
        }

        $data = $request->validate([
            'role'         => ['sometimes', Rule::in(User::ROLES)],
            'is_suspended' => ['sometimes', 'boolean'],
        ]);
        $changes = [];
        if (array_key_exists('role', $data) && $user->role !== $data['role']) {
            $changes['role'] = ['from' => $user->role, 'to' => $data['role']];
            $user->role = $data['role'];
        }
        if (array_key_exists('is_suspended', $data) && $user->is_suspended !== (bool) $data['is_suspended']) {
            $changes['is_suspended'] = ['from' => $user->is_suspended, 'to' => (bool) $data['is_suspended']];
            $user->is_suspended = (bool) $data['is_suspended'];
        }
        if ($changes) {
            $user->save();
            foreach ($changes as $field => $diff) {
                $action = match ($field) {
                    'role' => 'user.role_change',
                    'is_suspended' => $diff['to'] ? 'user.suspend' : 'user.unsuspend',
                    default => 'user.update',
                };
                AuditLogger::log($action, $user, $diff);
            }
        }

        return back()->with('status', 'Đã cập nhật người dùng.');
    }

    public function resetUserPassword(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin() || !$user->isAdmin(), 403);
        $newPassword = $request->input('password') ?: \Illuminate\Support\Str::random(10);
        $user->password = Hash::make($newPassword);
        $user->save();
        AuditLogger::log('user.password_reset', $user);
        return back()->with('status', "Đã reset mật khẩu cho {$user->email}: {$newPassword}");
    }

    public function shops(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Shop::with('owner')->withCount('floors')->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            });
        }
        return view('admin.shops', [
            'shops' => $query->paginate(20)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function destroyShop(Shop $shop)
    {
        $name = $shop->name;
        AuditLogger::log('shop.delete', $shop, ['name' => $name, 'slug' => $shop->slug]);
        $shop->delete();
        return back()->with('status', "Đã xoá shop \"{$name}\".");
    }

    // ───── Floors moderation ─────

    public function floors(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));
        $query = Floor::with(['shop.owner', 'reviewer'])->latest('updated_at');
        if (in_array($status, Floor::STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhereHas('shop', fn($s) => $s->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"));
            });
        }
        return view('admin.floors', [
            'floors' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'q' => $q,
        ]);
    }

    public function approveFloor(Request $request, Floor $floor)
    {
        $floor->update([
            'status'           => Floor::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at'      => now(),
            'reviewed_by_id'   => $request->user()->id,
        ]);
        AuditLogger::log('floor.approve', $floor);
        return back()->with('status', "Đã duyệt sơ đồ \"{$floor->name}\".");
    }

    public function rejectFloor(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);
        $floor->update([
            'status'           => Floor::STATUS_REJECTED,
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_at'      => now(),
            'reviewed_by_id'   => $request->user()->id,
        ]);
        AuditLogger::log('floor.reject', $floor, ['reason' => $data['rejection_reason']]);
        return back()->with('status', "Đã từ chối sơ đồ \"{$floor->name}\".");
    }

    public function lockFloor(Request $request, Floor $floor)
    {
        $next = !$floor->is_locked;
        $floor->update(['is_locked' => $next]);
        AuditLogger::log($next ? 'floor.lock' : 'floor.unlock', $floor);
        return back()->with('status', $next ? "Đã khoá sơ đồ." : "Đã mở khoá sơ đồ.");
    }

    // ───── Audit logs ─────

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user')->latest();
        if ($action = $request->query('action')) {
            $query->where('action', 'like', "{$action}%");
        }
        if ($targetType = $request->query('target_type')) {
            $query->where('target_type', $targetType);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        return view('admin.audit-logs', [
            'logs'         => $query->paginate(30)->withQueryString(),
            'action'       => $request->query('action'),
            'target_type'  => $request->query('target_type'),
            'user_id'      => $request->query('user_id'),
        ]);
    }
}
