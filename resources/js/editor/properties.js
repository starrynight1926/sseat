/**
 * Render properties panel for the selected node.
 * onChange(field, value) is called whenever a value changes.
 */
export function renderProperties(node, onChange) {
    const panel = document.getElementById('properties-panel');
    if (!panel) return;

    if (!node) {
        panel.innerHTML = `<p class="text-slate-400">Chọn một đối tượng để chỉnh sửa.</p>
            <div class="mt-4 rounded-md bg-slate-50 p-3 text-xs text-slate-500 leading-relaxed">
                <p class="mb-1 font-semibold text-slate-600">Phím tắt</p>
                <ul class="space-y-0.5">
                    <li><kbd>Ctrl+S</kbd> Lưu</li>
                    <li><kbd>Ctrl+Z</kbd> Undo</li>
                    <li><kbd>Ctrl+Y</kbd> Redo</li>
                    <li><kbd>Ctrl+D</kbd> Nhân đôi</li>
                    <li><kbd>Delete</kbd> Xoá</li>
                    <li><kbd>Space</kbd>+kéo: di chuyển canvas</li>
                    <li><kbd>Wheel</kbd> Zoom</li>
                </ul>
            </div>`;
        return;
    }

    const data = node.getAttr('appData') || {};
    const main = node.findOne('.main-shape') || node;
    const isCircle = main.getClassName?.() === 'Circle';
    const isText = main.getClassName?.() === 'Text';

    const w = isCircle ? main.radius() * 2 : (main.width ? main.width() : 0);
    const h = isCircle ? main.radius() * 2 : (main.height ? main.height() : 0);

    const sizeRow = isText ? '' : `
        <div class="prop-row">
            <label>Rộng (W)</label>
            <input data-field="width" type="number" value="${Math.round(w)}">
        </div>
        <div class="prop-row">
            <label>Cao (H)</label>
            <input data-field="height" type="number" value="${Math.round(h)}">
        </div>`;

    const labelRow = (data.type === 'label')
        ? `<div class="prop-row">
                <label>Nội dung</label>
                <input data-field="text" type="text" value="${escapeHtml(data.text || '')}" class="!w-44">
           </div>`
        : `<div class="prop-row">
                <label>Nhãn</label>
                <input data-field="label" type="text" value="${escapeHtml(data.label || '')}" class="!w-44">
           </div>`;

    const colorRow = isText ? '' : `
        <div class="prop-row">
            <label>Màu nền</label>
            <input data-field="fill" type="color" value="${toHex(main.fill?.() || '#ffffff')}" class="!w-16 !p-0 !h-8">
        </div>
        <div class="prop-row">
            <label>Viền</label>
            <input data-field="stroke" type="color" value="${toHex(main.stroke?.() || '#000000')}" class="!w-16 !p-0 !h-8">
        </div>`;

    panel.innerHTML = `
        <div class="mb-3 rounded-md bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700">
            ${typeLabel(data.type)} · <span class="font-mono text-[10px] opacity-60">${node.id()}</span>
        </div>
        ${labelRow}
        <div class="prop-row">
            <label>X</label>
            <input data-field="x" type="number" value="${Math.round(node.x())}">
        </div>
        <div class="prop-row">
            <label>Y</label>
            <input data-field="y" type="number" value="${Math.round(node.y())}">
        </div>
        ${sizeRow}
        <div class="prop-row">
            <label>Xoay (°)</label>
            <input data-field="rotation" type="number" value="${Math.round(node.rotation())}">
        </div>
        ${colorRow}
        <div class="mt-4 flex gap-2">
            <button id="prop-duplicate" class="btn-ghost flex-1">📑 Nhân đôi</button>
            <button id="prop-delete" class="btn-danger flex-1">🗑 Xoá</button>
        </div>
    `;

    panel.querySelectorAll('[data-field]').forEach(el => {
        el.addEventListener('change', (e) => onChange(el.dataset.field, e.target.value));
        if (el.type === 'color') {
            el.addEventListener('input', (e) => onChange(el.dataset.field, e.target.value));
        }
    });
    panel.querySelector('#prop-delete').onclick = () => {
        node.destroy();
        document.dispatchEvent(new CustomEvent('sseat:deselect'));
        // Reuse keyboard delete: simulate by triggering selectNode(null) via re-render
        renderProperties(null);
        node.getLayer()?.batchDraw();
    };
    panel.querySelector('#prop-duplicate').onclick = () => {
        // Trigger Ctrl+D
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'd', ctrlKey: true }));
    };
}

function typeLabel(type) {
    return ({
        'table-rect': 'Bàn vuông',
        'table-circle': 'Bàn tròn',
        'chair': 'Ghế',
        'wall': 'Tường',
        'door': 'Cửa',
        'label': 'Nhãn chữ',
    })[type] || type;
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[c]);
}

function toHex(c) {
    if (!c) return '#000000';
    if (c.startsWith('#')) return c.length === 7 ? c : '#000000';
    // rgb(...) → #
    const m = c.match(/\d+/g);
    if (!m || m.length < 3) return '#000000';
    return '#' + m.slice(0, 3).map(n => parseInt(n).toString(16).padStart(2, '0')).join('');
}
