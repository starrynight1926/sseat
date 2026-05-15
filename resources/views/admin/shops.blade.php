<x-admin-layout :title="'Cửa hàng'">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Cửa hàng ({{ $shops->total() }})</h1>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Tên hoặc slug…"
               class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
        <button class="btn-primary" type="submit">🔎 Tìm</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3">Tên</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Tầng</th>
                    <th class="px-4 py-3">Tạo lúc</th>
                    <th class="px-4 py-3">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shops as $s)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $s->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $s->slug }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($s->owner)
                                {{ $s->owner->name }} <span class="text-xs text-slate-400">({{ $s->owner->email }})</span>
                            @else
                                <span class="text-xs italic text-slate-400">(no owner)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $s->floors_count }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $s->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if ($s->floors_count > 0)
                                    <a href="{{ route('floors.view', [$s, $s->floors()->orderBy('order')->first()]) }}"
                                       target="_blank" class="btn-ghost">👁 Xem</a>
                                @endif
                                <form method="POST" action="{{ route('admin.shops.destroy', $s) }}"
                                      onsubmit="return confirm('Xoá shop {{ $s->name }} và toàn bộ tầng/ghế?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger" type="submit">🗑 Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Không có shop.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $shops->links() }}</div>
</x-admin-layout>
