# ARCH-PROJECT-003 — Scheduling, Dependencies, Constraints & CPM

**Parent:** BR-PROJECT-003
**Status:** Proposed
**BABOK Direction:** Solution Design → Architecture
**Traceability:** FR-PROJECT-003-001/FR-PROJECT-003-002; UT-PROJECT-003-*

## Design Goals

1. Transport-agnostic scheduling that FA and standalone use identically
   (DbConnectionInterface contract, ksf_FA_Common §ksf_common_db).
2. A **pure CPM engine** that is fully unit-testable inside/outside FA — it
   reads only `TaskService` rows + dependency rows, never touches db state, so
   it can be exercised through `PdoDbAdapter` OR `FaDbAdapter` by simple DI.
3. Cycle-safety at two layers: the repository/DAO guard AND the engine itself.
4. Schedule outputs are durable columns (ES/EF/LS/LF/slack/critical) on the
   task row, so the FA tab list + Reports + Gantt read one source of truth
   without recomputation.

## Structure

```
TaskService ──► TaskDependencyRepository (DAO, FaDbAdapter)
     │                        │
     └────────────► SchedulingService ──► CpmEngine (pure, no db)
                          │
                          ├─► persist ES/EF/LS/LF/slack/critical → tasks
                          └─► gantt/calendar consumers (BR-PROJECT-005)
```

- **CpmEngine** — Markov-style forward/backward passes; types `fs`,`ss`,`ff`,`sf`
  + lag; milestones are virtual zero-duration tasks; constraint types
  `start_no_earlier_than/start_no_later_than/must_start_on/finish_no_later_than/
  must_finish_on`. `detectCycle(projectId, exclusionId)` returns
  `['ok'=>false,'cycle'=>['TSK-0003','TSK-0001','TSK-0002','TSK-0003']]`.
- **SchedulingService** — orchestrator: loads task + dependency rows, runs
  CPM, persists schedule fields, exposes `criticalPath()` and `slack()`.
- **TaskDependencyService** — lifecycle CRUD with WorkflowHooksTrait
  (`task_dependency_before_save`/`..._new`/`..._after_save`, delete +
  activity log), same-project validation, self-loop/cycle rejection).

## Constraints & Conventions

- PHP 7.3 floor; no prepared statements — `FaDbAdapter` regex-prefixes table
  names and binds `?` literals. Engine uses only parameter primitives.
- Cycle error is data, never an exception — engine + service both return
  `ok=false` + message (testable, non-fatal).
- One constraint per task (`NULL` = unconstrained "ASAP"). New columns:
  `start_constraint` varchar(24) + `constraint_date` date on tasks;
  `is_milestone` tinyint(1) on tasks (zero-duration).
- New dictionary table `0_fa_pm_task_dependencies`.

## Security

All reads/writes run under SA_ksf_FA_ProjectManagementVIEW (view) — no new
security areas; RBAC/access mapping is unchanged.
