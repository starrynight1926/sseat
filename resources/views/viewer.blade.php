<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shop->name }} / {{ $floor->name }} — Viewer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-800 antialiased">
    @php
        $sseatCtx = [
            'mode' => 'viewer',
            'shop' => ['id' => $shop->id, 'slug' => $shop->slug, 'name' => $shop->name],
            'floor' => ['id' => $floor->id, 'name' => $floor->name, 'layout' => $floor->layout, 'bg_url' => $floor->bg_url],
            'floors' => $floors->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'view_url' => route('floors.view', [$shop, $f])])->values(),
        ];
    @endphp
    <script>
        window.__SSEAT__ = {!! json_encode($sseatCtx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>
    <div id="viewer-app" class="flex h-full flex-col">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-2 shadow-sm">
            <div class="flex items-center gap-3">
                <a href="{{ route('shops.show', $shop) }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ $shop->name }}</a>
                <h1 class="text-lg font-semibold text-slate-900">👁 {{ $floor->name }}</h1>
                <select id="floor-switcher" class="rounded border border-slate-300 px-2 py-1 text-sm">
                    @foreach ($floors as $f)
                        <option value="{{ route('floors.view', [$shop, $f]) }}" @selected($f->id === $floor->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Read-only</span>
            </div>
            <div class="flex items-center gap-1.5">
                <button id="btn-reset-status" class="btn-ghost" title="Reset tất cả ghế về trống">🔄 Reset ghế</button>
                <a href="{{ route('floors.edit', [$shop, $floor]) }}" class="btn-primary">✏ Sửa</a>
            </div>
        </header>

        <main class="relative flex-1 overflow-hidden bg-slate-200">
            <div id="viewer-container" class="absolute inset-0"></div>

            {{-- Empty state --}}
            <div id="viewer-empty" class="absolute inset-0 hidden items-center justify-center">
                <div class="max-w-md rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                    <div class="mb-3 text-5xl">🗺️</div>
                    <h2 class="mb-2 text-lg font-semibold text-slate-900">Tầng này chưa có layout</h2>
                    <p class="mb-4 text-sm text-slate-500">Mở editor để thiết kế mặt bằng.</p>
                    <a href="{{ route('floors.edit', [$shop, $floor]) }}" class="btn-primary inline-flex justify-center">✏ Đi đến Editor</a>
                </div>
            </div>

            {{-- Zoom controls --}}
            <div id="viewer-zoom" class="absolute bottom-4 left-1/2 hidden -translate-x-1/2 items-center gap-1 rounded-lg bg-white px-2 py-1 shadow-lg">
                <button id="vz-out" class="btn-icon">−</button>
                <span id="vz-label" class="w-14 text-center text-sm font-medium">100%</span>
                <button id="vz-in" class="btn-icon">+</button>
                <span class="mx-1 h-5 w-px bg-slate-200"></span>
                <button id="vz-fit" class="btn-icon" title="Fit">⛶</button>
            </div>
        </main>

    </div>
</body>
</html>
