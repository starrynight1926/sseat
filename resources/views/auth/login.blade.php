<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập — S-Seat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md items-center justify-center px-4">
        <div class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="mb-4 text-xl font-semibold">🪑 Đăng nhập S-Seat</h1>
            @if ($errors->any())
                <div class="mb-3 rounded bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Mật khẩu</label>
                    <input type="password" name="password" required
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4">
                    Ghi nhớ đăng nhập
                </label>
                <button type="submit" class="btn-primary w-full justify-center">Đăng nhập</button>
            </form>
            <p class="mt-4 text-center text-sm text-slate-500">
                Chưa có tài khoản? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Đăng ký</a>
            </p>
        </div>
    </main>
</body>
</html>
