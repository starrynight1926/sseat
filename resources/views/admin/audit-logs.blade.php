<x-admin-layout :title="'Nhật ký'">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Nhật ký hoạt động ({{ $logs->total() }})</h1>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
        <input type="text" name="action" value="{{ $action }}" placeholder="action prefix (vd: floor)"
               class="rounded border border-slate-300 px-3 py-2">
        <select name="target_type" class="rounded border border-slate-300 px-3 py-2">
            <option value="">— Loại đối tượng —</option>
            @foreach (['User','Shop','Floor'] as $t)
                <option value="{{ $t }}" @selected($target_type === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <input type="number" name="user_id" value="{{ $user_id }}" placeholder="user id"
               class="rounded border border-slate-300 px-3 py-2 w-32">
        <button class="btn-primary" type="submit">🔎 Lọc</button>
        <a href="{{ route('admin.audit-logs') }}" class="btn-ghost">↺ Xoá lọc</a>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3">Thời gian</th>
                    <th class="px-4 py-3">Người dùng</th>
                    <th class="px-4 py-3">Hành động</th>
                    <th class="px-4 py-3">Đối tượng</th>
                    <th class="px-4 py-3">Chi tiết</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-2 text-xs text-slate-500 whitespace-nowrap">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                            <div class="text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-2">
                            @if ($log->user)
                                <div class="font-medium text-slate-900">{{ $log->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                            @else
                                <span class="text-xs italic text-slate-400">— hệ thống —</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-xs">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs">
                            @if ($log->target_type)
                                <span class="text-slate-600">{{ $log->target_type }}#{{ $log->target_id }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs">
                            @if ($log->meta)
                                <pre class="whitespace-pre-wrap break-all text-xs text-slate-600">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ $log->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Chưa có log.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-admin-layout>
