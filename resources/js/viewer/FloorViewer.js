import Konva from 'konva';
import { createObject } from '../editor/objects.js';

const STORAGE_KEY = 'sseat:phase1:layout';
const STATUS_KEY  = 'sseat:phase1:chair-status';

const STATUS_COLORS = {
    available: { fill: '#fef3c7', stroke: '#f59e0b' },
    occupied:  { fill: '#fecaca', stroke: '#dc2626' },
};

let chairStatus = {}; // { [chairId]: 'available' | 'occupied' }

function loadStatus() {
    try { chairStatus = JSON.parse(localStorage.getItem(STATUS_KEY) || '{}'); }
    catch { chairStatus = {}; }
}
function saveStatus() {
    localStorage.setItem(STATUS_KEY, JSON.stringify(chairStatus));
}

const state = {
    stage: null,
    bgLayer: null,
    gridLayer: null,
    mainLayer: null,
    canvas: { width: 1600, height: 1000 },
};

function $(id) { return document.getElementById(id); }

export function initViewer() {
    const container = $('viewer-container');
    state.stage = new Konva.Stage({
        container,
        width: container.clientWidth,
        height: container.clientHeight,
    });
    state.bgLayer = new Konva.Layer({ listening: false });
    state.gridLayer = new Konva.Layer({ listening: false });
    state.mainLayer = new Konva.Layer();
    state.stage.add(state.bgLayer, state.gridLayer, state.mainLayer);

    loadStatus();
    bindUI();
    bindStageEvents();

    window.addEventListener('resize', () => {
        state.stage.width(container.clientWidth);
        state.stage.height(container.clientHeight);
    });

    // Auto-load: ?src=storage|hash data
    const params = new URLSearchParams(location.search);
    if (params.get('src') === 'storage') {
        loadFromStorage();
    } else if (location.hash.startsWith('#data=')) {
        try {
            const json = JSON.parse(decodeURIComponent(escape(atob(location.hash.slice(6)))));
            renderLayout(json);
        } catch (e) { console.error(e); }
    }
}

function bindUI() {
    const handleFile = (file) => {
        const r = new FileReader();
        r.onload = (e) => {
            try { renderLayout(JSON.parse(e.target.result)); }
            catch { alert('File JSON không hợp lệ'); }
        };
        r.readAsText(file);
    };
    $('file-import').onchange = (e) => { if (e.target.files[0]) handleFile(e.target.files[0]); };
    $('file-import-2').onchange = (e) => { if (e.target.files[0]) handleFile(e.target.files[0]); };

    $('btn-from-storage').onclick = loadFromStorage;
    $('btn-from-storage-2').onclick = loadFromStorage;

    const modal = $('paste-modal');
    $('btn-paste').onclick = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); $('paste-area').focus(); };
    $('paste-cancel').onclick = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    $('paste-ok').onclick = () => {
        try {
            const data = JSON.parse($('paste-area').value);
            renderLayout(data);
            modal.classList.add('hidden'); modal.classList.remove('flex');
        } catch { alert('JSON không hợp lệ'); }
    };

    $('btn-reset-status').onclick = () => {
        if (!confirm('Reset tất cả ghế về trạng thái trống?')) return;
        chairStatus = {};
        saveStatus();
        state.mainLayer.getChildren().forEach(n => {
            if ((n.getAttr('appData') || {}).type === 'chair') applyChairStatus(n, 'available');
        });
        state.mainLayer.batchDraw();
        let chairCount = 0, total = 0;
        state.mainLayer.getChildren().forEach(n => {
            total++;
            if ((n.getAttr('appData') || {}).type === 'chair') chairCount++;
        });
        updateMeta(total, chairCount, 0);
    };

    $('vz-in').onclick = () => zoomBy(1.2);
    $('vz-out').onclick = () => zoomBy(1 / 1.2);
    $('vz-fit').onclick = fitToScreen;
}

function bindStageEvents() {
    const stage = state.stage;
    let isPanning = false, lastPos = null;

    stage.on('mousedown touchstart', (e) => {
        isPanning = true;
        lastPos = { x: e.evt.clientX, y: e.evt.clientY };
        stage.container().style.cursor = 'grabbing';
    });
    stage.on('mousemove touchmove', (e) => {
        if (!isPanning) return;
        const dx = e.evt.clientX - lastPos.x;
        const dy = e.evt.clientY - lastPos.y;
        stage.position({ x: stage.x() + dx, y: stage.y() + dy });
        lastPos = { x: e.evt.clientX, y: e.evt.clientY };
        stage.batchDraw();
    });
    stage.on('mouseup touchend mouseleave', () => {
        isPanning = false;
        stage.container().style.cursor = 'grab';
    });
    stage.container().style.cursor = 'grab';

    stage.on('wheel', (e) => {
        e.evt.preventDefault();
        const oldScale = stage.scaleX();
        const pointer = stage.getPointerPosition();
        const mp = {
            x: (pointer.x - stage.x()) / oldScale,
            y: (pointer.y - stage.y()) / oldScale,
        };
        const factor = e.evt.deltaY > 0 ? 1 / 1.1 : 1.1;
        const newScale = Math.max(0.1, Math.min(5, oldScale * factor));
        stage.scale({ x: newScale, y: newScale });
        stage.position({ x: pointer.x - mp.x * newScale, y: pointer.y - mp.y * newScale });
        updateZoomLabel();
        stage.batchDraw();
    });
}

function zoomBy(factor) {
    const stage = state.stage;
    const oldScale = stage.scaleX();
    const center = { x: stage.width() / 2, y: stage.height() / 2 };
    const mp = {
        x: (center.x - stage.x()) / oldScale,
        y: (center.y - stage.y()) / oldScale,
    };
    const newScale = Math.max(0.1, Math.min(5, oldScale * factor));
    stage.scale({ x: newScale, y: newScale });
    stage.position({ x: center.x - mp.x * newScale, y: center.y - mp.y * newScale });
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

function updateZoomLabel() {
    $('vz-label').textContent = Math.round(state.stage.scaleX() * 100) + '%';
}

function loadFromStorage() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) { alert('Chưa có layout nào lưu trong trình duyệt này'); return; }
    try { renderLayout(JSON.parse(raw)); }
    catch { alert('Dữ liệu lưu bị hỏng'); }
}

function drawGrid() {
    state.gridLayer.destroyChildren();
    const { width, height } = state.canvas;
    state.gridLayer.add(new Konva.Rect({
        x: 0, y: 0, width, height,
        fill: state.bgLayer.hasChildren() ? undefined : '#ffffff',
        stroke: '#cbd5e1',
        strokeWidth: 1,
        listening: false,
    }));
    state.gridLayer.batchDraw();
}

async function renderLayout(data) {
    if (!data || !Array.isArray(data.objects)) {
        alert('JSON không có trường "objects"');
        return;
    }
    state.canvas = data.canvas || { width: 1600, height: 1000 };
    $('viewer-empty').classList.add('hidden');
    $('viewer-zoom').classList.remove('hidden');
    $('viewer-zoom').classList.add('flex');

    state.bgLayer.destroyChildren();
    state.mainLayer.destroyChildren();

    if (data.background_url) {
        await loadBg(data.background_url);
    }
    drawGrid();

    let chairCount = 0, occupiedCount = 0;
    data.objects.forEach(o => {
        const node = createObject(o.type, o);
        node.draggable(false);
        if (o.type === 'chair') {
            chairCount++;
            const chairId = o.id;
            const status = chairStatus[chairId] || 'available';
            if (status === 'occupied') occupiedCount++;
            applyChairStatus(node, status);
            // Hover cursor
            node.on('mouseenter', () => state.stage.container().style.cursor = 'pointer');
            node.on('mouseleave', () => state.stage.container().style.cursor = 'grab');
            // Click to toggle — but ignore if click was drag-pan
            let downPos = null;
            node.on('mousedown touchstart', (e) => { downPos = { x: e.evt.clientX, y: e.evt.clientY }; });
            node.on('click tap', (e) => {
                if (downPos) {
                    const dx = e.evt.clientX - downPos.x;
                    const dy = e.evt.clientY - downPos.y;
                    if (Math.hypot(dx, dy) > 4) return; // was a pan
                }
                e.cancelBubble = true;
                toggleChair(chairId, node);
            });
        } else {
            node.listening(false);
        }
        state.mainLayer.add(node);
    });
    state.mainLayer.batchDraw();

    updateMeta(data.objects.length, chairCount, occupiedCount);
    fitToScreen();
}

function updateMeta(total, chairCount, occupied) {
    const free = chairCount - occupied;
    $('viewer-meta').innerHTML =
        `${total} đối tượng · ` +
        `<span class="text-amber-600">● ${free} trống</span> / ` +
        `<span class="text-red-600">● ${occupied} có người</span> / ${chairCount} ghế`;
}

function applyChairStatus(node, status) {
    const main = node.findOne('.main-shape');
    if (!main) return;
    const c = STATUS_COLORS[status] || STATUS_COLORS.available;
    main.fill(c.fill);
    main.stroke(c.stroke);
    node.setAttr('chairStatus', status);
}

function toggleChair(chairId, node) {
    const cur = chairStatus[chairId] || 'available';
    const next = cur === 'available' ? 'occupied' : 'available';
    chairStatus[chairId] = next;
    saveStatus();
    applyChairStatus(node, next);
    state.mainLayer.batchDraw();

    // Recount for meta
    let chairCount = 0, occupiedCount = 0, total = 0;
    state.mainLayer.getChildren().forEach(n => {
        total++;
        const data = n.getAttr('appData') || {};
        if (data.type === 'chair') {
            chairCount++;
            if (n.getAttr('chairStatus') === 'occupied') occupiedCount++;
        }
    });
    updateMeta(total, chairCount, occupiedCount);
}

function loadBg(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            state.canvas.width = img.width;
            state.canvas.height = img.height;
            state.bgLayer.add(new Konva.Image({
                x: 0, y: 0, image: img, width: img.width, height: img.height,
                listening: false,
            }));
            state.bgLayer.batchDraw();
            resolve();
        };
        img.onerror = () => resolve();
        img.src = url;
    });
}
