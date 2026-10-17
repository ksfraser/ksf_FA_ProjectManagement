# FR-PROJECT-003-001 — Task Scheduling Constraints & Milestones

**Parent:** BR-PROJECT-003
**Status:** Proposed
**BABOK Direction:** Requirements Analysis → Specify & Model
**Traceability:** ARCH-PROJECT-003; UT-PROJECT-003-001-*

## Functional Requirement

The Tasks tab must let a manager model a schedule on the task record:

### FR-PROJECT-003-001-001 — Milestone flag

Task is flagged `is_milestone` (checkbox). A milestone has **zero duration** and
a **target date**; its milestone status is `Open`/`Complete`. Milestones are
excluded from hours aggregation but still participate in the CPM pass as
zero-duration anchors.

### FR-PROJECT-003-001-002 — Constraint anchors

Task carries, at most, one of:
- `start_no_earlier_than` — ES >= constraint_date
- `start_no_later_than` — LS <= constraint_date
- `must_start_on` — ES = LS = constraint_date (zero-slack anchor)
- `finish_no_earlier_than` — EF >= constraint_date
- `finish_no_later_than` / `must_finish_on` — LF <= constraint_date

Constraint semantics follow ScheduleJS/reactive-scheduling conventions.

### FR-PROJECT-003-001-003 — Scheduling run

`SchedulingService::scheduleProject($projectId)` computes a **pure CPM pass**
(forward + backward) and persists ES/EF/LS/LF/slack/critical per task. The pass
is a pure function of the task/dependency reads — no UI, no FA state — so it
is unit-testable in isolation (UT-PROJECT-003-001-001).

### FR-PROJECT-003-001-004 — Critical path surfacing

Tasks with `slack == 0` (within float tolerance 0.5d) are flagged `critical`.
The task list colours critical rows; the Reports tab shows critical-path count.

## Acceptance Criteria

1. A task with `is_milestone=1` and a target date schedules as zero-duration.
2. `start_no_earlier_than` raises ES but keeps LS free (floated schedule).
3. Cycles raise an error surfaced to the caller, not a raw exception.
4. `slack` is deterministic; critical-path membership is stable on re-runs.

## Related

- BR-PROJECT-001 §milestone billing; BR-PROJECT-002 (templates default milestone
  skeletons).
