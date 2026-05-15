<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — S-Seat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-slate-900 text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-3">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-semibold">⚙️ S-Seat Admin</a>
                <nav class="flex items-center gap-1 text-sm">
                    <a href="{{ route('admin.dashboard') }}"
                       class="rounded px-3 py-1.5 hover:bg-slate-800 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.users') }}"
                       class="rounded px-3 py-1.5 hover:bg-slate-800 {{ request()->routeIs('admin.users*') ? 'bg-slate-800' : '' }}">Người dùng</a>
                    <a href="{{ route('admin.shops') }}"
                       class="rounded px-3 py-1.5 hover:bg-slate-800 {{ request()->routeIs('admin.shops*') ? 'bg-slate-800' : '' }}">Cửa hàng</a>
                    <a href="{{ route('admin.floors') }}"
                       class="rounded px-3 py-1.5 hover:bg-slate-800 {{ request()->routeIs('admin.floors*') ? 'bg-slate-800' : '' }}">Sơ đồ</a>
                    <a href="{{ route('admin.audit-logs') }}"
                       class="rounded px-3 py-1.5 hover:bg-slate-800 {{ request()->routeIs('admin.audit*') ? 'bg-slate-800' : '' }}">Nhật ký</a>
                </nav>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('shops.index') }}" class="text-slate-300 hover:text-white">↩ Trang user</a>
                <span class="text-slate-300">{{ auth()->user()->name }}
                    <span class="ml-1 rounded-full bg-emerald-600 px-2 py-0.5 text-xs">{{ auth()->user()->role }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="rounded bg-slate-800 px-3 py-1.5 text-sm hover:bg-slate-700" type="submit">Đăng xuất</button>
                </form>
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto mt-4 max-w-6xl px-6">
            <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="mx-auto mt-4 max-w-6xl px-6">
            <div class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ $errors->first() }}</div>
        </div>
    @endif

    <main class="mx-auto max-w-6xl px-6 py-8">
        {{ $slot }}
    </main>
</body>
</html>
