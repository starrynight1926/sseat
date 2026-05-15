<x-admin-layout :title="'Dashboard'">
    <h1 class="mb-6 text-2xl font-semibold">Dashboard</h1>

    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
        @foreach ([
            ['label' => 'Người dùng',  'value' => $stats['users'],     'color' => 'bg-blue-100 text-blue-800'],
            ['label' => 'Admin',       'value' => $stats['admins'],    'color' => 'bg-emerald-100 text-emerald-800'],
            ['label' => 'Bị khoá',     'value' => $stats['suspended'], 'color' => 'bg-red-100 text-red-800'],
            ['label' => 'Cửa hàng',    'value' => $stats['shops'],     'color' => 'bg-purple-100 text-purple-800'],
            ['label' => 'Tầng',        'value' => $stats['floors'],    'color' => 'bg-amber-100 text-amber-800'],
            ['label' => 'Ghế (DB)',    'value' => $stats['chairs'],    'color' => 'bg-pink-100 text-pink-800'],
            ['label' => 'Chờ duyệt',   'value' => $stats['pending'],   'color' => 'bg-orange-100 text-orange-800'],
        ] as $card)
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-2 inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $card['color'] }}">{{ $card['label'] }}</div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($card['value']) }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">User mới</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentUsers as $u)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <div class="font-medium text-slate-900">{{ $u->name }}</div>
                            <div class="text-xs text-slate-500">{{ $u->email }}</div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $u->role }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Chưa có user.</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Cửa hàng mới</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentShops as $s)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <div class="font-medium text-slate-900">{{ $s->name }}</div>
                            <div class="text-xs text-slate-500">{{ $s->owner->email ?? '— (no owner)' }}</div>
                        </div>
                        <span class="text-xs text-slate-400">{{ $s->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Chưa có shop.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
