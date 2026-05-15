# Project Instructions

## Memory
At the start of every conversation, read the memory index at `.claude/memory/MEMORY.md` and load any relevant memory files referenced there. This is the project's persistent memory — use it instead of the global memory path.

When saving new memories, always write them to `.claude/memory/` in this project (not the global `~/.claude/projects/` path).

## Skills (Auto-load)
At the start of every conversation, scan `.claude/skills/` for skill folders. Each skill folder must contain a `SKILL.md` file. Read all `SKILL.md` files to understand available capabilities.

Current skills:
- `.claude/skills/user-story-ac-writer/` — Sinh User Story & AC chuẩn INVEST + Given-When-Then
