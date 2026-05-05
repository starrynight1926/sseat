<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $shop->name }} / {{ $floor->name }} — Editor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-800 antialiased">
    @php
        $sseatCtx = [
            'mode' => 'editor',
            'shop' => ['id' => $shop->id, 'slug' => $shop->slug, 'name' => $shop->name],
            'floor' => ['id' => $floor->id, 'name' => $floor->name, 'layout' => $floor->layout, 'bg_url' => $floor->bg_url],
            'floors' => $floors->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'edit_url' => route('floors.edit', [$shop, $f])])->values(),
        ];
    @endphp
    <script>
        window.__SSEAT__ = {!! json_encode($sseatCtx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    </script>
    <div id="app" class="flex h-full flex-col">
        {{-- Top toolbar --}}
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-2 shadow-sm">
            <div class="flex items-center gap-3">
                <a href="{{ route('shops.show', $shop) }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ $shop->name }}</a>
                <h1 class="text-lg font-semibold text-slate-900">{{ $floor->name }}</h1>
                <select id="floor-switcher" class="rounded border border-slate-300 px-2 py-1 text-sm">
                    @foreach ($floors as $f)
                        <option value="{{ route('floors.edit', [$shop, $f]) }}" @selected($f->id === $floor->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('floors.view', [$shop, $floor]) }}" class="btn-ghost" target="_blank">👁 Xem</a>
            </div>
            <div class="flex items-center gap-1.5">
                <button id="btn-undo" class="btn-ghost" title="Undo (Ctrl+Z)">↶ Undo</button>
                <button id="btn-redo" class="btn-ghost" title="Redo (Ctrl+Y)">↷ Redo</button>
                <span class="mx-2 h-6 w-px bg-slate-200"></span>
                <button id="btn-save" class="btn-primary">💾 Lưu</button>
                <button id="btn-load" class="btn-ghost">📂 Tải lại</button>
                <button id="btn-export" class="btn-ghost">⬇ Export JSON</button>
                <label class="btn-ghost cursor-pointer">
                    ⬆ Import JSON
                    <input id="file-import" type="file" accept="application/json" class="hidden">
                </label>
                <span class="mx-2 h-6 w-px bg-slate-200"></span>
                <button id="btn-clear" class="btn-danger">🗑 Xoá tất cả</button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            {{-- Left sidebar: tools --}}
            <aside class="w-56 shrink-0 overflow-y-auto border-r border-slate-200 bg-white p-3">
                <h2 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Đồ vật</h2>
                <div class="grid grid-cols-2 gap-2">
                    <button class="tool-btn" data-tool="table-rect">
                        <div class="h-8 w-8 rounded border-2 border-blue-500 bg-blue-50"></div>
                        <span>Bàn vuông</span>
                    </button>
                    <button class="tool-btn" data-tool="table-circle">
                        <div class="h-8 w-8 rounded-full border-2 border-blue-500 bg-blue-50"></div>
                        <span>Bàn tròn</span>
                    </button>
                    <button class="tool-btn" data-tool="chair">
                        <div class="h-6 w-6 rounded-md border-2 border-amber-500 bg-amber-50"></div>
                        <span>Ghế vuông</span>
                    </button>
                    <button class="tool-btn" data-tool="chair-round">
                        <div class="h-6 w-6 rounded-full border-2 border-amber-500 bg-amber-50"></div>
                        <span>Ghế tròn</span>
                    </button>
                    <button class="tool-btn" data-tool="wall">
                        <div class="h-2 w-8 bg-slate-700"></div>
                        <span>Tường</span>
                    </button>
                    <button class="tool-btn" data-tool="label">
                        <div class="text-sm font-bold">T</div>
                        <span>Nhãn chữ</span>
                    </button>
                    <button class="tool-btn" data-tool="door">
                        <div class="h-6 w-6 border-l-2 border-t-2 border-amber-700"></div>
                        <span>Cửa</span>
                    </button>
                </div>

                <h2 class="mb-2 mt-5 text-xs font-bold uppercase tracking-wider text-slate-500">Nền (Background)</h2>
                <label class="btn-ghost block w-full cursor-pointer text-center">
                    🖼 Tải ảnh nền
                    <input id="file-bg" type="file" accept="image/*" class="hidden">
                </label>
                <button id="btn-remove-bg" class="btn-ghost mt-2 w-full">Xoá ảnh nền</button>

                <h2 class="mb-2 mt-5 text-xs font-bold uppercase tracking-wider text-slate-500">Canvas</h2>
                <div class="space-y-2 text-sm">
                    <label class="flex items-center justify-between">
                        <span>Lưới</span>
                        <input id="chk-grid" type="checkbox" checked class="h-4 w-4">
                    </label>
                    <label class="flex items-center justify-between">
                        <span>Snap lưới</span>
                        <input id="chk-snap" type="checkbox" checked class="h-4 w-4">
                    </label>
                    <label class="flex items-center justify-between gap-2">
                        <span>Cỡ lưới</span>
                        <input id="inp-grid-size" type="number" min="5" max="100" value="20" class="w-16 rounded border border-slate-300 px-2 py-1 text-right">
                    </label>
                </div>
            </aside>

            {{-- Canvas area --}}
            <main class="relative flex-1 overflow-hidden bg-slate-200">
                <div id="canvas-container" class="absolute inset-0"></div>

                {{-- Zoom controls --}}
                <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-lg bg-white px-2 py-1 shadow-lg">
                    <button id="btn-zoom-out" class="btn-icon">−</button>
                    <span id="zoom-label" class="w-14 text-center text-sm font-medium">100%</span>
                    <button id="btn-zoom-in" class="btn-icon">+</button>
                    <span class="mx-1 h-5 w-px bg-slate-200"></span>
                    <button id="btn-zoom-fit" class="btn-icon" title="Fit">⛶</button>
                    <button id="btn-zoom-reset" class="btn-icon" title="100%">1:1</button>
                </div>

                <div id="status-bar" class="pointer-events-none absolute bottom-4 right-4 rounded-md bg-white/90 px-3 py-1 text-xs text-slate-600 shadow">
                    Sẵn sàng
                </div>
            </main>

            {{-- Right sidebar: properties --}}
            <aside class="w-72 shrink-0 overflow-y-auto border-l border-slate-200 bg-white p-3">
                <h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Thuộc tính</h2>
                <div id="properties-panel" class="text-sm">
                    <p class="text-slate-400">Chọn một đối tượng để chỉnh sửa.</p>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
