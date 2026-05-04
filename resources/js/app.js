import './bootstrap';
import { initEditor } from './editor/FloorEditor.js';
import { initViewer } from './viewer/FloorViewer.js';

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('canvas-container')) initEditor();
    if (document.getElementById('viewer-container')) initViewer();
});

