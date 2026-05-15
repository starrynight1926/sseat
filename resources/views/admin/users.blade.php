<x-admin-layout :title="'Người dùng'">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Người dùng ({{ $users->total() }})</h1>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Tên hoặc email…"
               class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
        <select name="role" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Tất cả role —</option>
            @foreach (\App\Models\User::ROLES as $r)
                <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
            @endforeach
        </select>
        <button class="btn-primary" type="submit">🔎 Tìm</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tên</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Shops</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $u->role }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $u->shops_count }}</td>
                        <td class="px-4 py-3">
                            @if ($u->is_suspended)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">Bị khoá</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Hoạt động</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($u->id !== auth()->id())
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Toggle suspend --}}
                                    <form method="POST" action="{{ route('admin.users.update', $u) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_suspended" value="{{ $u->is_suspended ? 0 : 1 }}">
                                        <button class="{{ $u->is_suspended ? 'btn-ghost' : 'btn-danger' }}" type="submit"
                                                onclick="return confirm('{{ $u->is_suspended ? 'Mở khoá' : 'Khoá' }} tài khoản {{ $u->email }}?')">
                                            {{ $u->is_suspended ? '🔓 Mở khoá' : '🔒 Khoá' }}
                                        </button>
                                    </form>
                                    {{-- Role change (super admin only) --}}
                                    @if (auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('admin.users.update', $u) }}" class="flex items-center gap-1">
                                            @csrf @method('PATCH')
                                            <select name="role" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                                @foreach (\App\Models\User::ROLES as $r)
                                                    <option value="{{ $r }}" @selected($u->role === $r)>{{ $r }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn-ghost text-xs" type="submit">Lưu</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.reset-password', $u) }}"
                                              onsubmit="return confirm('Reset mật khẩu cho {{ $u->email }}?')">
                                            @csrf
                                            <button class="btn-ghost" type="submit">🔑 Reset pass</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-400">— bạn —</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Không có user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-admin-layout>
