<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floor Plan Viewer — Read-only</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-800 antialiased">
    <div id="viewer-app" class="flex h-full flex-col">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-2 shadow-sm">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold text-slate-900">👁 Floor Viewer</h1>
                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Read-only</span>
                <span id="viewer-meta" class="text-xs text-slate-500"></span>
            </div>
            <div class="flex items-center gap-1.5">
                <label class="btn-ghost cursor-pointer">
                    📂 Mở JSON
                    <input id="file-import" type="file" accept="application/json" class="hidden">
                </label>
                <button id="btn-paste" class="btn-ghost">📋 Paste JSON</button>
                <button id="btn-from-storage" class="btn-ghost">💾 Tải từ localStorage</button>
                <button id="btn-reset-status" class="btn-ghost" title="Reset tất cả ghế về trống">🔄 Reset ghế</button>
                <span class="mx-2 h-6 w-px bg-slate-200"></span>
                <a href="/" class="btn-primary">← Editor</a>
            </div>
        </header>

        <main class="relative flex-1 overflow-hidden bg-slate-200">
            <div id="viewer-container" class="absolute inset-0"></div>

            {{-- Empty state --}}
            <div id="viewer-empty" class="absolute inset-0 flex items-center justify-center">
                <div class="max-w-md rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                    <div class="mb-3 text-5xl">🗺️</div>
                    <h2 class="mb-2 text-lg font-semibold text-slate-900">Chưa có layout</h2>
                    <p class="mb-4 text-sm text-slate-500">Tải file JSON đã export từ editor, paste nội dung JSON, hoặc tải layout đã lưu trong trình duyệt.</p>
                    <div class="flex flex-col gap-2">
                        <label class="btn-primary cursor-pointer justify-center">
                            📂 Mở file JSON
                            <input id="file-import-2" type="file" accept="application/json" class="hidden">
                        </label>
                        <button id="btn-from-storage-2" class="btn-ghost justify-center">💾 Tải layout đã lưu</button>
                    </div>
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

        {{-- Paste modal --}}
        <div id="paste-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
                <h3 class="mb-2 text-lg font-semibold">Paste nội dung JSON</h3>
                <textarea id="paste-area" class="h-64 w-full rounded border border-slate-300 p-2 font-mono text-xs" placeholder='{"format_version":"1.0", ...}'></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button id="paste-cancel" class="btn-ghost">Huỷ</button>
                    <button id="paste-ok" class="btn-primary">Hiển thị</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
