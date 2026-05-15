import Konva from 'konva';
import { createObject, OBJECT_DEFAULTS } from './objects.js';
import { renderProperties } from './properties.js';
import { History } from './history.js';

const FORMAT_VERSION = '1.0';
const CTX = (typeof window !== 'undefined' && window.__SSEAT__) || null;
const STORAGE_KEY = CTX?.floor ? `sseat:floor:${CTX.floor.id}:layout` : 'sseat:phase1:layout';

const state = {
    stage: null,
    bgLayer: null,
    gridLayer: null,
    mainLayer: null,
    transformer: null,
    bgImage: null,
    bgUrl: null,
    selectedNode: null,    // legacy single ref (= first of selectedNodes)
    selectedNodes: [],
    selectionRect: null,
    gridSize: 20,
    showGrid: true,
    snap: true,
    history: new History(),
    nextId: 1,
    canvas: { width: 1600, height: 1000 },
};

function $(id) { return document.getElementById(id); }

function setStatus(msg, kind = 'info') {
    const el = $('status-bar');
    if (!el) return;
    el.textContent = msg;
    el.className = `pointer-events-none absolute bottom-4 right-4 rounded-md px-3 py-1 text-xs shadow ${
        kind === 'error' ? 'bg-red-600 text-white' :
        kind === 'success' ? 'bg-emerald-600 text-white' :
        'bg-white/90 text-slate-600'
    }`;
}

function uid() { return 'obj_' + (state.nextId++).toString(36) + '_' + Date.now().toString(36); }

function snapValue(v) {
    if (!state.snap) return v;
    // Half-grid snap: align to 0.5 cell so user can place at 1, 1.5, 2 cells
    const step = state.gridSize / 2;
    return Math.round(v / step) * step;
}

// Clipboard for copy/paste
let clipboard = [];

// ───────── Init ─────────
export function initEditor() {
    const container = $('canvas-container');
    state.stage = new Konva.Stage({
        container,
        width: container.clientWidth,
        height: container.clientHeight,
        draggable: false,
    });

    state.bgLayer = new Konva.Layer({ listening: false });
    state.gridLayer = new Konva.Layer({ listening: false });
    state.mainLayer = new Konva.Layer();

    state.stage.add(state.bgLayer, state.gridLayer, state.mainLayer);

    state.transformer = new Konva.Transformer({
        rotateEnabled: true,
        keepRatio: false,
        anchorSize: 8,
        borderStroke: '#3b82f6',
        anchorStroke: '#3b82f6',
        anchorFill: '#fff',
    });
    state.mainLayer.add(state.transformer);

    drawGrid();
    bindUI();
    bindStageEvents();
    bindKeyboard();

    // Auto load: prefer DB layout from server context, fallback to localStorage
    const initial = CTX?.floor?.layout;
    if (initial && Object.keys(initial).length) {
        deserialize(initial);
    } else if (!loadFromStorage()) {
        centerStage();
    }

    window.addEventListener('resize', () => {
        state.stage.width(container.clientWidth);
        state.stage.height(container.clientHeight);
        drawGrid();
    });

    state.history.push(serialize());
    setStatus('Sẵn sàng. Click vào công cụ để thêm đối tượng.', 'info');
}

function centerStage() {
    const c = state.stage;
    c.position({
        x: (c.width() - state.canvas.width) / 2,
        y: (c.height() - state.canvas.height) / 2,
    });
    c.batchDraw();
}

// ───────── Grid ─────────
function drawGrid() {
    state.gridLayer.destroyChildren();
    if (!state.showGrid) {
        state.gridLayer.batchDraw();
        return;
    }
    const { width, height } = state.canvas;
    // Canvas frame — only fill white if there's NO background image
    state.gridLayer.add(new Konva.Rect({
        x: 0, y: 0, width, height,
        fill: state.bgImage ? undefined : '#ffffff',
        stroke: '#cbd5e1',
        strokeWidth: 1,
        listening: false,
    }));
    const step = state.gridSize;
    for (let x = 0; x <= width; x += step) {
        state.gridLayer.add(new Konva.Line({
            points: [x, 0, x, height],
            stroke: x % (step * 5) === 0 ? '#cbd5e1' : '#e2e8f0',
            strokeWidth: 1,
        }));
    }
    for (let y = 0; y <= height; y += step) {
        state.gridLayer.add(new Konva.Line({
            points: [0, y, width, y],
            stroke: y % (step * 5) === 0 ? '#cbd5e1' : '#e2e8f0',
            strokeWidth: 1,
        }));
    }
    state.gridLayer.batchDraw();
}

// ───────── Add object ─────────
function addObjectAtCenter(toolType) {
    const stage = state.stage;
    const cx = (stage.width() / 2 - stage.x()) / stage.scaleX();
    const cy = (stage.height() / 2 - stage.y()) / stage.scaleY();
    const node = createObject(toolType, {
        x: snapValue(cx),
        y: snapValue(cy),
        id: uid(),
    });
    state.mainLayer.add(node);
    attachNodeHandlers(node);
    selectNode(node);
    state.transformer.moveToTop();
    pushHistory();
    setStatus(`Đã thêm ${OBJECT_DEFAULTS[toolType]?.label || toolType}`, 'success');
}

function attachNodeHandlers(node) {
    node.on('click tap', (e) => {
        e.cancelBubble = true;
        const additive = e.evt && (e.evt.shiftKey || e.evt.ctrlKey || e.evt.metaKey);
        if (additive) {
            toggleNodeInSelection(node);
        } else {
            selectNode(node);
        }
    });
    node.on('dragstart', () => {
        node.moveToTop();
        state.transformer.moveToTop();
    });
    node.on('dragmove', () => {
        if (state.snap) {
            node.position({ x: snapValue(node.x()), y: snapValue(node.y()) });
        }
    });
    node.on('dragend transformend', () => {
        if (state.snap) {
            node.position({ x: snapValue(node.x()), y: snapValue(node.y()) });
        }
        pushHistory();
        if (state.selectedNode === node) renderProperties(node, onPropertyChange);
    });
}

// ───────── Selection ─────────
function selectNode(node) {
    if (!node) { selectNodes([]); return; }
    selectNodes([node]);
}

function selectNodes(nodes) {
    state.selectedNodes = nodes.slice();
    state.selectedNode = nodes[0] || null;
    state.transformer.nodes(nodes);
    state.mainLayer.batchDraw();
    renderProperties(nodes.length === 1 ? nodes[0] : null, onPropertyChange);
}

function toggleNodeInSelection(node) {
    const i = state.selectedNodes.indexOf(node);
    const next = state.selectedNodes.slice();
    if (i >= 0) next.splice(i, 1); else next.push(node);
    selectNodes(next);
}

function onPropertyChange(field, value) {
    const node = state.selectedNode;
    if (!node) return;
    const data = node.getAttr('appData') || {};
    if (field === 'label' || field === 'text') {
        data[field] = value;
        node.setAttr('appData', data);
        // Update visible text child if any
        const textChild = node.findOne('.label-text');
        if (textChild) textChild.text(value);
        if (node.getClassName() === 'Text') node.text(value);
    } else if (field === 'fill' || field === 'stroke') {
        const main = node.findOne('.main-shape') || node;
        main[field](value);
        data[field] = value;
        node.setAttr('appData', data);
    } else if (['x', 'y', 'width', 'height', 'rotation'].includes(field)) {
        const v = parseFloat(value) || 0;
        if (field === 'width' || field === 'height') {
            const main = node.findOne('.main-shape');
            if (main) {
                if (main.getClassName() === 'Circle') {
                    main.radius(v / 2);
                } else {
                    main[field](v);
                }
            }
        } else {
            node[field](v);
        }
    }
    state.mainLayer.batchDraw();
    pushHistory();
}

// ───────── Stage events ─────────
function bindStageEvents() {
    const stage = state.stage;

    let suppressNextClick = false;
    stage.on('click tap', (e) => {
        if (suppressNextClick) { suppressNextClick = false; return; }
        if (e.target === stage || e.target.getParent() === state.gridLayer) {
            selectNode(null);
        }
    });

    // Pan with middle mouse / space + drag
    let isPanning = false;
    let lastPos = null;
    let spaceDown = false;
    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space' && document.activeElement.tagName !== 'INPUT') {
            spaceDown = true;
            stage.container().style.cursor = 'grab';
        }
    });
    window.addEventListener('keyup', (e) => {
        if (e.code === 'Space') {
            spaceDown = false;
            stage.container().style.cursor = '';
        }
    });
    // Rubber-band selection on empty area (left-click + drag)
    let rubberBand = null;
    let rubberStart = null;

    stage.on('mousedown touchstart', (e) => {
        const evt = e.evt;
        if (evt.button === 1 || (spaceDown && evt.button === 0)) {
            isPanning = true;
            lastPos = { x: evt.clientX, y: evt.clientY };
            stage.container().style.cursor = 'grabbing';
            evt.preventDefault();
            return;
        }
        // Start rubber-band only when clicking empty area with left mouse
        if (evt.button === 0 && (e.target === stage || e.target.getParent() === state.gridLayer || e.target.getParent() === state.bgLayer)) {
            const pointer = stage.getPointerPosition();
            const sx = stage.scaleX();
            rubberStart = {
                x: (pointer.x - stage.x()) / sx,
                y: (pointer.y - stage.y()) / sx,
            };
            rubberBand = new Konva.Rect({
                x: rubberStart.x, y: rubberStart.y, width: 0, height: 0,
                fill: 'rgba(59,130,246,0.12)',
                stroke: '#3b82f6', strokeWidth: 1, dash: [4, 4],
                listening: false,
            });
            state.mainLayer.add(rubberBand);
        }
    });
    stage.on('mousemove touchmove', (e) => {
        if (isPanning) {
            const evt = e.evt;
            const dx = evt.clientX - lastPos.x;
            const dy = evt.clientY - lastPos.y;
            stage.position({ x: stage.x() + dx, y: stage.y() + dy });
            lastPos = { x: evt.clientX, y: evt.clientY };
            stage.batchDraw();
            return;
        }
        if (rubberBand && rubberStart) {
            const pointer = stage.getPointerPosition();
            const sx = stage.scaleX();
            const cur = {
                x: (pointer.x - stage.x()) / sx,
                y: (pointer.y - stage.y()) / sx,
            };
            rubberBand.setAttrs({
                x: Math.min(rubberStart.x, cur.x),
                y: Math.min(rubberStart.y, cur.y),
                width: Math.abs(cur.x - rubberStart.x),
                height: Math.abs(cur.y - rubberStart.y),
            });
            state.mainLayer.batchDraw();
        }
    });
    stage.on('mouseup touchend', (e) => {
        isPanning = false;
        stage.container().style.cursor = spaceDown ? 'grab' : '';
        if (rubberBand) {
            const box = rubberBand.getClientRect({ relativeTo: state.mainLayer });
            rubberBand.destroy();
            rubberBand = null;
            rubberStart = null;
            if (box.width > 3 && box.height > 3) {
                const candidates = state.mainLayer.getChildren().filter(n => n !== state.transformer && n.getAttr('appData'));
                const picked = candidates.filter(n => Konva.Util.haveIntersection(box, n.getClientRect({ relativeTo: state.mainLayer })));
                const additive = e.evt && (e.evt.shiftKey || e.evt.ctrlKey || e.evt.metaKey);
                const next = additive
                    ? Array.from(new Set([...state.selectedNodes, ...picked]))
                    : picked;
                selectNodes(next);
                suppressNextClick = true;
            }
            state.mainLayer.batchDraw();
        }
    });

    // Zoom with wheel
    stage.on('wheel', (e) => {
        e.evt.preventDefault();
        const oldScale = stage.scaleX();
        const pointer = stage.getPointerPosition();
        const mousePointTo = {
            x: (pointer.x - stage.x()) / oldScale,
            y: (pointer.y - stage.y()) / oldScale,
        };
        const direction = e.evt.deltaY > 0 ? -1 : 1;
        const factor = 1.1;
        let newScale = direction > 0 ? oldScale * factor : oldScale / factor;
        newScale = Math.max(0.1, Math.min(5, newScale));
        stage.scale({ x: newScale, y: newScale });
        stage.position({
            x: pointer.x - mousePointTo.x * newScale,
            y: pointer.y - mousePointTo.y * newScale,
        });
        updateZoomLabel();
        stage.batchDraw();
    });
}

function updateZoomLabel() {
    $('zoom-label').textContent = Math.round(state.stage.scaleX() * 100) + '%';
}

// ───────── Keyboard ─────────
function bindKeyboard() {
    window.addEventListener('keydown', (e) => {
        if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
            e.preventDefault(); doUndo();
        } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.key.toLowerCase() === 'z' && e.shiftKey))) {
            e.preventDefault(); doRedo();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault(); saveToServer();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
            e.preventDefault(); duplicateSelected();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
            e.preventDefault(); copySelected();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'v') {
            e.preventDefault(); pasteClipboard();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') {
            e.preventDefault();
            const all = state.mainLayer.getChildren().filter(n => n !== state.transformer && n.getAttr('appData'));
            selectNodes(all);
        } else if (e.key === 'Delete' || e.key === 'Backspace') {
            if (state.selectedNodes.length) {
                e.preventDefault();
                deleteSelected();
            }
        } else if (e.key === 'Escape') {
            selectNode(null);
        }
    });
}

function deleteSelected() {
    if (!state.selectedNodes.length) return;
    const n = state.selectedNodes.length;
    state.selectedNodes.forEach(node => node.destroy());
    selectNodes([]);
    state.mainLayer.batchDraw();
    pushHistory();
    setStatus(`Đã xoá ${n} đối tượng`, 'success');
}

function duplicateSelected() {
    if (!state.selectedNodes.length) return;
    const created = state.selectedNodes.map(src => {
        const data = serializeNode(src);
        data.id = uid();
        data.x += 20; data.y += 20;
        const node = createObject(data.type, data);
        state.mainLayer.add(node);
        attachNodeHandlers(node);
        return node;
    });
    selectNodes(created);
    state.transformer.moveToTop();
    pushHistory();
}

function copySelected() {
    if (!state.selectedNodes.length) return;
    clipboard = state.selectedNodes.map(serializeNode);
    setStatus(`Đã copy ${clipboard.length} đối tượng (Ctrl+V để dán)`, 'success');
}

function pasteClipboard() {
    if (!clipboard.length) return;
    // Compute group bounding box top-left for offset preservation
    const minX = Math.min(...clipboard.map(d => d.x));
    const minY = Math.min(...clipboard.map(d => d.y));
    const offset = state.gridSize; // 1 cell offset
    const created = clipboard.map(d => {
        const data = { ...d, id: uid(), x: d.x + offset, y: d.y + offset };
        const node = createObject(data.type, data);
        state.mainLayer.add(node);
        attachNodeHandlers(node);
        return node;
    });
    selectNodes(created);
    state.transformer.moveToTop();
    pushHistory();
    setStatus(`Đã dán ${created.length} đối tượng`, 'success');
    void minX; void minY;
}

// ───────── UI bindings ─────────
function bindUI() {
    document.querySelectorAll('[data-tool]').forEach(btn => {
        btn.addEventListener('click', () => addObjectAtCenter(btn.dataset.tool));
    });

    $('btn-undo').onclick = doUndo;
    $('btn-redo').onclick = doRedo;
    $('btn-save').onclick = () => saveToServer();
    $('btn-load').onclick = () => { location.reload(); };
    $('btn-export').onclick = exportJSON;
    $('btn-clear').onclick = clearAll;

    $('file-import').onchange = (e) => {
        const file = e.target.files[0];
        if (file) importJSON(file);
        e.target.value = '';
    };

    $('file-bg').onchange = async (e) => {
        const file = e.target.files[0];
        if (file) await uploadBackground(file);
        e.target.value = '';
    };
    $('btn-remove-bg').onclick = removeBackground;

    $('chk-grid').onchange = (e) => { state.showGrid = e.target.checked; drawGrid(); };
    $('chk-snap').onchange = (e) => { state.snap = e.target.checked; };
    $('inp-grid-size').onchange = (e) => {
        state.gridSize = Math.max(5, parseInt(e.target.value) || 20);
        drawGrid();
    };

    $('btn-zoom-in').onclick = () => zoomBy(1.2);
    $('btn-zoom-out').onclick = () => zoomBy(1 / 1.2);
    $('btn-zoom-reset').onclick = () => { state.stage.scale({ x: 1, y: 1 }); centerStage(); updateZoomLabel(); };
    $('btn-zoom-fit').onclick = fitToScreen;

    const switcher = $('floor-switcher');
    if (switcher) switcher.onchange = (e) => { window.location.href = e.target.value; };
}

function zoomBy(factor) {
    const stage = state.stage;
    const oldScale = stage.scaleX();
    const center = { x: stage.width() / 2, y: stage.height() / 2 };
    const mousePointTo = {
        x: (center.x - stage.x()) / oldScale,
        y: (center.y - stage.y()) / oldScale,
    };
    const newScale = Math.max(0.1, Math.min(5, oldScale * factor));
    stage.scale({ x: newScale, y: newScale });
    stage.position({
        x: center.x - mousePointTo.x * newScale,
        y: center.y - mousePointTo.y * newScale,
    });
    updateZoomLabel();
    stage.batchDraw();
}

function fitToScreen() {
    const stage = state.stage;
    const padding = 40;
    const sx = (stage.width() - padding * 2) / state.canvas.width;
    const sy = (stage.height() - padding * 2) / state.canvas.height;
    const s = Math.min(sx, sy);
    stage.scale({ x: s, y: s });
    stage.position({
        x: (stage.width() - state.canvas.width * s) / 2,
        y: (stage.height() - state.canvas.height * s) / 2,
    });
    updateZoomLabel();
    stage.batchDraw();
}

// ───────── Background ─────────
async function uploadBackground(file) {
    setStatus('Đang tải ảnh nền...');
    const fd = new FormData();
    fd.append('image', file);
    try {
        const res = await fetch('/api/upload-image', {
            method: 'POST',
            body: fd,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch { throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`); }
        if (!res.ok || !json.success) {
            const msg = json?.message || json?.error?.message || JSON.stringify(json?.errors || json);
            throw new Error(msg);
        }
        await setBackgroundImage(json.data.url);
        state.bgUrl = json.data.url;
        pushHistory();
        setStatus('Đã tải ảnh nền', 'success');
    } catch (err) {
        console.error('[upload-image]', err);
        setStatus('Lỗi upload: ' + err.message, 'error');
    }
}

function setBackgroundImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            state.bgLayer.destroyChildren();
            state.canvas.width = img.width;
            state.canvas.height = img.height;
            state.bgImage = new Konva.Image({
                x: 0, y: 0, image: img,
                width: img.width, height: img.height,
                listening: false,
            });
            state.bgLayer.add(state.bgImage);
            state.bgLayer.batchDraw();
            drawGrid();
            resolve();
        };
        img.onerror = (e) => {
            console.error('[bg-image] failed to load', url, e);
            setStatus('Không tải được ảnh nền từ URL: ' + url, 'error');
            resolve();
        };
        img.src = url;
    });
}

function removeBackground() {
    state.bgLayer.destroyChildren();
    state.bgImage = null;
    state.bgUrl = null;
    state.bgLayer.batchDraw();
    pushHistory();
    setStatus('Đã xoá ảnh nền', 'success');
}

// ───────── Serialization ─────────
function serializeNode(node) {
    const data = node.getAttr('appData') || {};
    const main = node.findOne('.main-shape') || node;
    return {
        id: node.id(),
        type: data.type,
        x: node.x(),
        y: node.y(),
        rotation: node.rotation(),
        scaleX: node.scaleX(),
        scaleY: node.scaleY(),
        width: main.width ? main.width() : undefined,
        height: main.height ? main.height() : undefined,
        radius: main.radius ? main.radius() : undefined,
        fill: main.fill ? main.fill() : undefined,
        stroke: main.stroke ? main.stroke() : undefined,
        label: data.label,
        text: data.text,
        fontSize: data.fontSize,
    };
}

function serialize() {
    const objects = state.mainLayer.getChildren()
        .filter(n => n !== state.transformer && n.getAttr('appData'))
        .map(serializeNode);
    return {
        format_version: FORMAT_VERSION,
        canvas: { ...state.canvas },
        background_url: state.bgUrl,
        grid: { size: state.gridSize, show: state.showGrid, snap: state.snap },
        objects,
    };
}

async function deserialize(data) {
    // Clear
    state.mainLayer.getChildren()
        .filter(n => n !== state.transformer)
        .forEach(n => n.destroy());
    selectNode(null);

    state.canvas = data.canvas || { width: 1600, height: 1000 };
    state.bgUrl = data.background_url || null;
    if (data.grid) {
        state.gridSize = data.grid.size ?? 20;
        state.showGrid = data.grid.show ?? true;
        state.snap = data.grid.snap ?? true;
        $('inp-grid-size').value = state.gridSize;
        $('chk-grid').checked = state.showGrid;
        $('chk-snap').checked = state.snap;
    }

    state.bgLayer.destroyChildren();
    if (state.bgUrl) await setBackgroundImage(state.bgUrl);
    drawGrid();

    (data.objects || []).forEach(o => {
        const node = createObject(o.type, o);
        state.mainLayer.add(node);
        attachNodeHandlers(node);
    });
    state.transformer.moveToTop();
    state.mainLayer.batchDraw();
}

// ───────── Storage ─────────
function saveToStorage() {
    const data = serialize();
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch (e) { /* quota */ }
}

async function saveToServer() {
    if (!CTX?.floor?.id) {
        saveToStorage();
        setStatus('Đã lưu vào trình duyệt (không có tầng)', 'success');
        return;
    }
    const data = serialize();
    setStatus('Đang lưu...');
    try {
        const res = await fetch(`/api/floors/${CTX.floor.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ layout: data, bg_url: state.bgUrl }),
        });
        if (!res.ok) {
            // Special-case: floor locked by admin (HTTP 423)
            let msg = 'HTTP ' + res.status;
            try { const j = await res.json(); if (j.message) msg = j.message; } catch {}
            throw new Error(msg);
        }
        saveToStorage();
        setStatus('Đã lưu vào database', 'success');
    } catch (err) {
        console.error('[save]', err);
        saveToStorage();
        setStatus('Lỗi lưu DB (đã lưu localStorage): ' + err.message, 'error');
    }
}

function loadFromStorage() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return false;
    try {
        const data = JSON.parse(raw);
        deserialize(data);
        return true;
    } catch (e) {
        console.error(e);
        return false;
    }
}

function clearAll() {
    if (!confirm('Xoá toàn bộ layout? Hành động này không thể hoàn tác.')) return;
    state.mainLayer.getChildren()
        .filter(n => n !== state.transformer)
        .forEach(n => n.destroy());
    removeBackground();
    selectNode(null);
    state.mainLayer.batchDraw();
    pushHistory();
    setStatus('Đã xoá layout', 'success');
}

// ───────── Import / Export ─────────
function exportJSON() {
    const data = serialize();
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `seatmap-${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
    setStatus('Đã export JSON', 'success');
}

function importJSON(file) {
    const reader = new FileReader();
    reader.onload = async (e) => {
        try {
            const data = JSON.parse(e.target.result);
            if (data.format_version !== FORMAT_VERSION) {
                if (!confirm('Format version khác. Vẫn tiếp tục import?')) return;
            }
            await deserialize(data);
            pushHistory();
            setStatus('Đã import JSON', 'success');
        } catch (err) {
            setStatus('File JSON không hợp lệ', 'error');
        }
    };
    reader.readAsText(file);
}

// ───────── History ─────────
function pushHistory() {
    state.history.push(serialize());
    saveToStorage();
}

async function doUndo() {
    const snapshot = state.history.undo();
    if (snapshot) { await deserialize(snapshot); setStatus('Undo'); }
}
async function doRedo() {
    const snapshot = state.history.redo();
    if (snapshot) { await deserialize(snapshot); setStatus('Redo'); }
}
