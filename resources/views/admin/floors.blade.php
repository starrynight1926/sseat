<x-admin-layout :title="'Sơ đồ'">
    @php
        $statusColors = [
            'draft'    => 'bg-slate-100 text-slate-700',
            'pending'  => 'bg-orange-100 text-orange-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Sơ đồ ({{ $floors->total() }})</h1>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Tên tầng / shop / slug…"
               class="flex-1 rounded border border-slate-300 px-3 py-2 text-sm">
        <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Tất cả status —</option>
            @foreach (\App\Models\Floor::STATUSES as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button class="btn-primary" type="submit">🔎 Lọc</button>
    </form>

    <div class="space-y-3">
        @forelse ($floors as $f)
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ $f->name }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $statusColors[$f->status] ?? 'bg-slate-100' }}">{{ $f->status }}</span>
                            @if ($f->is_locked)
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">🔒 Khoá</span>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Shop: <a href="{{ route('admin.shops', ['q' => $f->shop->slug]) }}" class="text-blue-600 hover:underline">{{ $f->shop->name }}</a>
                            ({{ $f->shop->slug }})
                            · Owner: {{ $f->shop->owner->email ?? '—' }}
                            · Cập nhật {{ $f->updated_at->diffForHumans() }}
                            @if ($f->reviewer)
                                · Duyệt bởi <em>{{ $f->reviewer->email }}</em> {{ optional($f->reviewed_at)->diffForHumans() }}
                            @endif
                        </div>
                        @if ($f->rejection_reason)
                            <div class="mt-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                <strong>Lý do từ chối:</strong> {{ $f->rejection_reason }}
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('floors.view', [$f->shop, $f]) }}" target="_blank" class="btn-ghost">👁 Xem</a>
                        @if ($f->status !== 'approved')
                            <form method="POST" action="{{ route('admin.floors.approve', $f) }}">@csrf
                                <button class="btn-primary" type="submit">✅ Duyệt</button>
                            </form>
                        @endif
                        @if ($f->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.floors.reject', $f) }}" class="flex items-center gap-1"
                                  onsubmit="this.querySelector('input[name=rejection_reason]').value || alert('Nhập lý do!') || event.preventDefault();">
                                @csrf
                                <input type="text" name="rejection_reason" placeholder="Lý do từ chối"
                                       class="rounded border border-slate-300 px-2 py-1 text-xs" maxlength="500">
                                <button class="btn-danger" type="submit">⛔ Từ chối</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.floors.lock', $f) }}">@csrf
                            <button class="btn-ghost" type="submit">{{ $f->is_locked ? '🔓 Mở khoá' : '🔒 Khoá' }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">Không có sơ đồ.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $floors->links() }}</div>
</x-admin-layout>
