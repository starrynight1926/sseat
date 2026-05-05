<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $shop->name }} — S-Seat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('shops.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Cửa hàng</a>
                <h1 class="text-xl font-semibold">{{ $shop->name }}</h1>
            </div>
            <form method="POST" action="{{ route('shops.destroy', $shop) }}" onsubmit="return confirm('Xoá cửa hàng và toàn bộ tầng?')">
                @csrf @method('DELETE')
                <button class="btn-danger" type="submit">🗑 Xoá cửa hàng</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-8">
        @if ($shop->description)
            <p class="mb-6 text-slate-600">{{ $shop->description }}</p>
        @endif

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Tầng ({{ $shop->floors->count() }})</h2>
            <button id="btn-add-floor" class="btn-primary">+ Thêm tầng</button>
        </div>

        <div id="floors-list" class="space-y-2">
            @foreach ($shop->floors as $floor)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-floor-id="{{ $floor->id }}">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $floor->name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $floor->updated_at->diffForHumans() }} ·
                            {{ is_array($floor->layout) ? count($floor->layout['objects'] ?? []) : 0 }} đối tượng
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn-ghost btn-copy-link"
                                data-url="{{ route('floors.view', [$shop, $floor]) }}"
                                title="Sao chép link công khai cho khách">🔗 Link</button>
                        <a href="{{ route('floors.view', [$shop, $floor]) }}" target="_blank" class="btn-ghost">👁 Xem</a>
                        <a href="{{ route('floors.operate', [$shop, $floor]) }}" class="btn-primary">🛎️ Vận hành</a>
                        <a href="{{ route('floors.edit', [$shop, $floor]) }}" class="btn-ghost">🗺️ Sơ đồ</a>
                        <button class="btn-danger btn-delete-floor" data-id="{{ $floor->id }}">🗑</button>
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    <script>
        const SHOP = @json(['id' => $shop->id, 'slug' => $shop->slug]);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        document.getElementById('btn-add-floor').addEventListener('click', async () => {
            const name = prompt('Tên tầng mới:', 'Tầng ' + (document.querySelectorAll('[data-floor-id]').length + 1));
            if (!name) return;
            const res = await fetch(`/api/shops/${SHOP.slug}/floors`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ name }),
            });
            if (res.ok) location.reload();
            else alert('Lỗi tạo tầng');
        });

        document.querySelectorAll('.btn-copy-link').forEach(btn => {
            btn.addEventListener('click', async () => {
                const url = new URL(btn.dataset.url, location.origin).href;
                try {
                    await navigator.clipboard.writeText(url);
                    const old = btn.innerHTML;
                    btn.innerHTML = '✓ Đã copy';
                    setTimeout(() => { btn.innerHTML = old; }, 1500);
                } catch {
                    prompt('Sao chép link bên dưới:', url);
                }
            });
        });

        document.querySelectorAll('.btn-delete-floor').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Xoá tầng này?')) return;
                const id = btn.dataset.id;
                const res = await fetch(`/api/floors/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                if (res.ok) location.reload();
                else alert('Lỗi xoá tầng');
            });
        });
    </script>
</body>
</html>
