---
name: Always read project plans before implementing
description: Must read external plan files (plan_gpt.md, plan_windsurf_kimi26.md, PM/plan_pm.md, BA/plan_ba.md, Dev/plan_dev.md) and README rules before starting any work
type: feedback
originSessionId: 46f94f4a-3472-4518-aae3-90f49ffa9037
---
Always read the project documentation in the external folder "Lara - S Seat" (on RaiDrive) before implementing anything. This includes plan files, BA specs, PM DoD criteria, Dev technical specs, and README collaboration rules.

**Why:** User relies on these plans as the source of truth for requirements, DoD, and phase definitions. Skipping them leads to incomplete implementations (missing audit logs, tests, docs) and misaligned role structures.

**How to apply:** At the start of every task, read the relevant plan files first. Cross-check implementation against DoD criteria (unit tests >80%, QA sign-off, docs updated, no critical bugs). Don't mark work as done until DoD is met.
