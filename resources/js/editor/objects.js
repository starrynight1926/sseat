import Konva from 'konva';

export const OBJECT_DEFAULTS = {
    'table-rect':   { label: 'Bàn vuông', width: 100, height: 100, fill: '#dbeafe', stroke: '#3b82f6' },
    'table-circle': { label: 'Bàn tròn',  radius: 50,              fill: '#dbeafe', stroke: '#3b82f6' },
    'chair':        { label: 'Ghế vuông', width: 36,  height: 36,  fill: '#fef3c7', stroke: '#f59e0b' },
    'chair-round':  { label: 'Ghế tròn',  radius: 18,              fill: '#fef3c7', stroke: '#f59e0b' },
    'wall':         { label: 'Tường',     width: 200, height: 8,   fill: '#374151', stroke: '#1f2937' },
    'door':         { label: 'Cửa',       width: 60,  height: 60,  fill: 'transparent', stroke: '#a16207' },
    'label':        { label: 'Nhãn',      text: 'Nhãn chữ', fontSize: 18 },
};

/**
 * Build a Konva.Group containing a "main shape" + optional label text.
 * appData stores logical info for serialization.
 */
export function createObject(type, opts = {}) {
    const def = OBJECT_DEFAULTS[type] || {};
    const merged = { ...def, ...opts };

    const group = new Konva.Group({
        id: opts.id,
        x: merged.x ?? 0,
        y: merged.y ?? 0,
        rotation: merged.rotation ?? 0,
        scaleX: merged.scaleX ?? 1,
        scaleY: merged.scaleY ?? 1,
        draggable: true,
    });
    group.setAttr('appData', {
        type,
        label: merged.label,
        text: merged.text,
        fontSize: merged.fontSize,
        fill: merged.fill,
        stroke: merged.stroke,
    });

    let mainShape;
    switch (type) {
        case 'wall':
            // Solid bar, no stroke — stroke on a thin rect looks like an extra piece.
            mainShape = new Konva.Rect({
                name: 'main-shape',
                x: 0, y: 0,
                width: merged.width,
                height: merged.height,
                fill: merged.fill,
                strokeWidth: 0,
                strokeScaleEnabled: false,
            });
            group.add(mainShape);
            break;

        case 'table-rect':
        case 'door':
        case 'chair':
            mainShape = new Konva.Rect({
                name: 'main-shape',
                x: 0, y: 0,
                width: merged.width,
                height: merged.height,
                fill: merged.fill,
                stroke: merged.stroke,
                strokeWidth: 2,
                strokeScaleEnabled: false,
                cornerRadius: type === 'chair' ? 6 : 4,
            });
            if (type === 'door') {
                mainShape.dash([6, 4]);
            }
            group.add(mainShape);
            break;

        case 'table-circle':
        case 'chair-round':
            mainShape = new Konva.Circle({
                name: 'main-shape',
                x: (merged.width || merged.radius * 2) / 2,
                y: (merged.height || merged.radius * 2) / 2,
                radius: merged.radius || (merged.width ? merged.width / 2 : 50),
                fill: merged.fill,
                stroke: merged.stroke,
                strokeWidth: 2,
                strokeScaleEnabled: false,
            });
            group.add(mainShape);
            break;

        case 'label':
            mainShape = new Konva.Text({
                name: 'main-shape',
                x: 0, y: 0,
                text: merged.text || 'Nhãn',
                fontSize: merged.fontSize || 18,
                fontFamily: 'Inter, system-ui, sans-serif',
                fill: '#111827',
                padding: 4,
            });
            group.add(mainShape);
            break;

        default:
            mainShape = new Konva.Rect({
                name: 'main-shape',
                width: 60, height: 60, fill: '#e5e7eb', stroke: '#9ca3af', strokeWidth: 1,
            });
            group.add(mainShape);
    }

    // Optional small label inside table
    if ((type === 'table-rect' || type === 'table-circle') && merged.label) {
        const isCircle = type === 'table-circle';
        const w = isCircle ? mainShape.radius() * 2 : mainShape.width();
        const h = isCircle ? mainShape.radius() * 2 : mainShape.height();
        const offX = isCircle ? 0 : 0;
        const offY = isCircle ? 0 : 0;
        const text = new Konva.Text({
            name: 'label-text',
            x: offX, y: offY,
            width: w,
            height: h,
            text: merged.label,
            fontSize: 12,
            fontFamily: 'Inter, system-ui, sans-serif',
            fill: '#1e3a8a',
            align: 'center',
            verticalAlign: 'middle',
            listening: false,
        });
        group.add(text);
    }

    return group;
}
