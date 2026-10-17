# BR-PROJECT-003 — Project Scheduling, Milestones & Dependencies

**Module:** ksf_FA_ProjectManagement
**Status:** Proposed
**BABOK Direction:** Strategy Analysis → Future State
**Traceability:** FR-PROJECT-003-001 / FR-PROJECT-003-002; ARCH-PROJECT-003; UT-PROJECT-003-*

## Business Requirement

PM must support the classic project-scheduler feature set (dotProject / OpenProject
timeline parity) so teams can plan, sequence and track work against a schedule,
not just an out-of-the-box task list:

1. **Milestones** — zero-duration tasks carrying a target date; used for phase
   gates, client deliverables and CPM anchors.
2. **Dependencies** — typed constraints between tasks (FS, SS, FF, SF) with
   optional lag, so that successor tasks start only after their predecessors
   complete.
3. **Date constraints** per task — "start no earlier than", "start no later
   than", "start on", "finish no earlier/later than", "finish by" anchors that
   pin the schedule independently of the CPM calculation.
4. **Scheduling calculation** — forward and backward CPM passes compute
   ES/EF/LS/LF with slack; the **critical path** (tasks with zero slack) is
   surfaced, and float/slack is shown per task so managers can re-plan.
5. **Cycle protection** — dependency cycles must be detected and rejected with a
   clear, user-actionable message.

## Business Value

- **Predictability** — a computed schedule (not hand-picked dates) makes end dates
  trustworthy and lets managers see the critical path at a glance.
- **Planning velocity** — typing "wire ends at plumbing finished +2d" is faster and
  safer than editing successor dates by hand.
- **Change visibility** — moving a milestone instantly re-spreads the impacted
  successors via the CPM engine; no silent date drift.

## Scope

### In Scope
- Milestone flag + target date on tasks
- Task dependencies (FS/SS/FF/SF, lag) with validation + cycle detection
- Task constraints (ES/EF-nth date anchors)
- CPM scheduling engine (pure, transport-agnostic) producing ES/EF/LS/LF/slack
- Critical path + slack surfaced in the task/report UI
- Constraint-aware start/end persistence on tasks

### Out of Scope (follow-ups)
- Resource-level levelling (beyond schedule) — BR-PROJECT-006
- Multi-project cross-linking, sub-project rollups

## Related Requirements

- BR-PROJECT-001 (fixed price contracts — milestone billing tie-in)
- BR-PROJECT-005 (calendar + Gantt feed — consumes computed schedule)
- BR-PROJECT-002 (templates — milestones/dependency skeleton reused)
