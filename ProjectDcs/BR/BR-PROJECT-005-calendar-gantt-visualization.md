# BR-PROJECT-005 — Calendar Feed & Gantt Visualization

**Module:** ksf_FA_ProjectManagement
**Status:** Proposed
**BABOK Direction:** Requirements Analysis → Define Future State
**Traceability:** FR-PROJECT-005-001 / FR-PROJECT-005-002; ARCH-PROJECT-005;
UT-PROJECT-005-*

## Business Requirement

The PM module must expose project schedule data to two consumers:

1. **Calendar feed** — project deadlines, task/milestone target dates,
   per-assignment deadlines surface as native **Calendar module entries**
   through the shared `calendar_entry_create/update/delete` hook protocol so
   stakeholders and invitees see PM dates on their FA calendar without PM
   coupling to the Calendar module.
2. **Gantt visualization** — a server-rendered Gantt/baseline timeline
   (rolled up from the CPM engine, ARCH-PROJECT-003) with task bars,
   dependency arrows and milestone diamonds, renderable inside the Reports
   tab and printable.

## Business Value

- **One schedule, many views** — the CPM output feeds both the Calendar feed
  and the Gantt, so dates never diverge between tab, report and calendar.
- **Cross-module events without coupling** — PM emits entries via
  `hook_invoke_first('calendar_entry_create', ...)`, and the FA Calendar
  module (listener) materializes them. PM stays transport-agnostic.
- **Planning comprehension** — critical-path tasks and float caveats are
  visible on the timeline, not hidden in a table.

## Scope

### In Scope
- `PmCalendarEmitter` service: builds entry payloads and invokes the calendar
  hooks (create/update/delete/query) for projects, tasks, milestones and
  assignments.
- `GanttTimelineService` + `GanttRenderer`: pure layout (date buckets, bar
  spans, milestone diamonds, dependency arrows) from CPM scheduled rows;
  server-side HTML/CSS render (no new JS asset).
- Reports-tab integration point for the Gantt view.

### Out of Scope (follow-ups)
- Interactive drag-drop Gantt editing (dates still edited via the task form).
- Read-back of calendar RSVP status into PM.
- Multi-project rollup Gantt (per-project scope only for now, templates BR-002).

## Related

- BR-PROJECT-003 (scheduling/CPM — the feed + gantt consume its output)
- BR-PROJECT-004 (documents — milestone SOW/certificate links may be shown on
  the timeline)
