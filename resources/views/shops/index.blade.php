<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>S-Seat — Danh sách cửa hàng</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold">🪑 S-Seat — Cửa hàng của tôi</h1>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Phase 3</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-slate-600">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn-ghost" type="submit">Đăng xuất</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-8">
        <section class="mb-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Tạo cửa hàng mới</h2>
            <form method="POST" action="{{ route('shops.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Tên cửa hàng *</label>
                    <input name="name" required maxlength="120" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="VD: Cafe Sky View">
                </div>
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Mô tả</label>
                    <input name="description" maxlength="500" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Tuỳ chọn">
                </div>
                <button type="submit" class="btn-primary h-10">+ Tạo</button>
            </form>
            @if ($errors->any())
                <p class="mt-2 text-sm text-red-600">{{ $errors->first() }}</p>
            @endif
        </section>

        <section>
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500">Danh sách ({{ $shops->count() }})</h2>
            @if ($shops->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    Chưa có cửa hàng nào. Tạo cửa hàng đầu tiên bên trên.
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($shops as $shop)
                        <a href="{{ route('shops.show', $shop) }}" class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-400 hover:shadow">
                            <div class="flex items-start justify-between">
                                <h3 class="font-semibold text-slate-900 group-hover:text-blue-600">{{ $shop->name }}</h3>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $shop->floors_count }} tầng</span>
                            </div>
                            @if ($shop->description)
                                <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ $shop->description }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">/{{ $shop->slug }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</body>
</html>
