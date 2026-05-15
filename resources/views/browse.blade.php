<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt sơ đồ — S-Seat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold">🪑 S-Seat — Duyệt sơ đồ</h1>
                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Khách</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <span class="text-slate-600">{{ auth()->user()->name }}
                        <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs">{{ auth()->user()->role }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn-ghost" type="submit">Đăng xuất</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn-primary">Đăng ký</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-8">
        @if (session('status'))
            <div class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">{{ session('status') }}</div>
        @endif

        <form method="GET" class="mb-6 flex gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="Tìm theo tên cửa hàng…"
                   class="flex-1 rounded border border-slate-300 bg-white px-3 py-2 text-sm">
            <button class="btn-primary" type="submit">🔎 Tìm</button>
        </form>

        @if ($shops->isEmpty())
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                Chưa có cửa hàng nào có sơ đồ được duyệt công khai.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($shops as $shop)
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-900">{{ $shop->name }}</h3>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{{ $shop->approved_floors_count }} tầng</span>
                        </div>
                        @if ($shop->description)
                            <p class="mb-3 line-clamp-2 text-xs text-slate-500">{{ $shop->description }}</p>
                        @endif
                        <ul class="space-y-1">
                            @foreach ($shop->floors as $floor)
                                <li>
                                    <a href="{{ route('floors.view', [$shop, $floor]) }}" target="_blank"
                                       class="flex items-center justify-between rounded border border-slate-200 px-3 py-2 text-sm hover:border-blue-400 hover:bg-blue-50">
                                        <span>👁 {{ $floor->name }}</span>
                                        <span class="text-xs text-slate-400">→</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $shops->links() }}</div>
        @endif
    </main>
</body>
</html>
