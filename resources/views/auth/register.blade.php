<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng ký — S-Seat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md items-center justify-center px-4">
        <div class="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="mb-4 text-xl font-semibold">🪑 Đăng ký S-Seat</h1>
            @if ($errors->any())
                <div class="mb-3 rounded bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Tên</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Mật khẩu</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nhập lại mật khẩu</label>
                    <input type="password" name="password_confirmation" required minlength="6"
                           class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Tạo tài khoản</button>
            </form>
            <p class="mt-4 text-center text-sm text-slate-500">
                Đã có tài khoản? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Đăng nhập</a>
            </p>
        </div>
    </main>
</body>
</html>
