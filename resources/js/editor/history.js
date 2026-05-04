/**
 * Simple snapshot-based undo/redo stack.
 * Stores serialized JSON snapshots; small and reliable.
 */
export class History {
    constructor(limit = 50) {
        this.stack = [];
        this.cursor = -1;
        this.limit = limit;
    }
    push(snapshot) {
        // Drop redo tail
        this.stack = this.stack.slice(0, this.cursor + 1);
        this.stack.push(JSON.parse(JSON.stringify(snapshot)));
        if (this.stack.length > this.limit) this.stack.shift();
        this.cursor = this.stack.length - 1;
    }
    undo() {
        if (this.cursor <= 0) return null;
        this.cursor--;
        return JSON.parse(JSON.stringify(this.stack[this.cursor]));
    }
    redo() {
        if (this.cursor >= this.stack.length - 1) return null;
        this.cursor++;
        return JSON.parse(JSON.stringify(this.stack[this.cursor]));
    }
    canUndo() { return this.cursor > 0; }
    canRedo() { return this.cursor < this.stack.length - 1; }
}
