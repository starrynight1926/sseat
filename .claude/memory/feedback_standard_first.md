---
name: Always advise standard-compliant structure
description: Must recommend correct Claude Code conventions (e.g. .claude/skills/ not custom folders) — never guess or improvise folder structures
type: feedback
---
Always advise the correct, standard Claude Code folder structure. Don't improvise or make up paths.

**Why:** User was given wrong folder path (.claude/project knowledge/) instead of the correct `.claude/skills/`. User expects accurate, standard-compliant guidance.

**How to apply:** Before recommending any folder structure or convention, verify it's the actual Claude Code standard. If unsure, say so — don't guess.
