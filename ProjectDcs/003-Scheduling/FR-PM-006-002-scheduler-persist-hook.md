# FR-PM-006-002 — Scheduler: transport-agnostic schedule persistence

| | |
|---|---|
| ID | FR-PM-006-002 |
| Related | BR-PM-006; UT-PM-006-002-* |
| Status | Approved (package) |

### Requirement
`SchedulingService::schedule(projectsId, startDate, tasks, dependencies)`
must run the engine, persist per-task ES/EF/LS/LF/slack/critical + project
duration + critical set, and fire a single `schedule_saved` workflow hook
(payload: project_id, duration, critical[], ok) using the module's
WorkflowHooksTrait registration `registerWorkflowType('schedule','pm_sched')`.

### Acceptance
- UT-PM-006-002-001 persists results via injected repository (no FA calls).
- UT-PM-006-002-002 fires `schedule_saved` exactly once on success.
- UT-PM-006-002-003 ok=false path returns early, no hook fired.
- UT-PM-006-002-004 default null endDate uses project-end too.
