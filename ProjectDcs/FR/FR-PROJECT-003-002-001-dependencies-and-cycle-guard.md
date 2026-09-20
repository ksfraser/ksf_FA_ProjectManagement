# FR-PROJECT-003-002-001 — Dependencies & Cycle Guard

**Parent:** BR-PROJECT-003
**Status:** Proposed
**BABOK Direction:** Solution Evaluation → Validate Design
**Traceability:** ARCH-PROJECT-003; UT-PROJECT-003-002-001-*

## Functional Requirement

### FR-PROJECT-003-002-001-001 — Dependency dictionary

New dictionary table `0_fa_pm_task_dependencies`:

- `dependency_id` PK, `task_id` FK → tasks, `predecessor_task_id` FK → tasks,
  `dependency_type` enum(`fs`,`ss`,`ff`,`sf`), `lag_days` int default 0,
  uniql (`task_id`,`predecessor_task_id`,`dependency_type`), `created_at`.

### FR-PROJECT-003-002-001-002 — Single entry point

All dependency writes go through `TaskDependencyService`:

- `create(data)` / `update(id, data)` / `delete(id)` — mirror TaskService
  lifecycle (before_save → save → new/edited → after_save; before_delete →
  delete → after_delete) via `WorkflowHooksTrait` with record type `task_dependency`.
- Validation: task + predecessor must belong to the **same project**;
  `task_id != predecessor_task_id` (no self-loop); type in (fs/ss/ff/sf);
  `lag_days` int ≥ 0.
- `_sameProject()` reads project_id from the parent tasks table (not from any
  caller-supplied field) — no trust in user input.

### FR-PROJECT-003-002-001-003 — Cycle guard (pre-write)

`create`/`update` run a **cycle pre-check** through the CPM engine
(`SchedulingService::detectCycle(projectId, exclusionId)`):

- Builds the effective project dependency graph with the *candidate row*
  applied, runs a DFS cycle scan.
- On a cycle → `return false` + a user-actionable message (`Cycle: TSK-0003 →
  TSK-0001 → TSK-0002 → TSK-0003`) and NO row is written. Both service methods
  accept an optional `?array &$errors` out-param so the caller can surface it
  without exceptions (testable).

### FR-PROJECT-003-002-001-004 — DAG invariant enforced at engine level

`CpmEngine::forwardPass()`/`backwardPass()` return
`['ok'=>false,'error'=>'cycle_detected','cycle'=>[...]]` instead of running an
infinite loop — the engine is defensive by contract, not relying on the service
guard alone (defence-in-depth).

## Acceptance Criteria

1. Adding a dependency that closes a cycle is rejected with cycle path detail.
2. Cross-project dependency is rejected as validation error.
3. Self-loop rejected.
4. Cycle detected during a CPM run produces a graceful engine result, never a hang.
