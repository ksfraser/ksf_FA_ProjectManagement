# ARCH-PROJECT-005 — Calendar Feed & Gantt (integration layer)

**Parent:** BR-PROJECT-005
**Status:** Proposed
**Traceability:** FR-PROJECT-005-001-*, FR-PROJECT-005-002-*;
UT-PROJECT-005-001-*, UT-PROJECT-005-002-*

## Design Goal

PM publishes schedule dates to the FA Calendar and renders a server-side
Gantt — **both transport-agnostic**, hook-driven, zero new infra. PM stays
an **emitter** on the calendar protocol; the listener is ksf_FA_Calendar
(existing `calendar_entry_*` registry, EVENTS.md).

## Calendar feed (PmCalendarEmitter)

- `PmCalendarEmitter` implements `PmCalendarEmitterInterface`
  (`calendar_entries()`, `calendarEntriesQuery()`).
- Wiring (per AGENTS conventions in `hooks.php` of every FA module):

```php
// PM hooks.php — register as emitter on the calendar protocol
$data = ['entity_type' => 'fa_pm_project'];
hook_invoke_all('calendar_entry_create', $data); // on project create
```

- The Calendar module already listens (`calendar_entry_create/update/delete`)
  and stores into `0_cal_entries`. PM needs **no** DB writes to Calendar;
  it only emits.
- Calendar type mapping: project → calendar_type `pm_project`;
  milestone → `pm_milestone`; task → `pm_task`. Date source = the CPM
  schedule (ES/EF from FR-PROJECT-003-001), not raw user dates, so the
  calendar always reflects the *computed* plan.
- Best-effort: wrap emit in try/catch; on failure log + continue (the
  transaction that saved the project must NOT roll back because Calendar
  is unavailable).

## Gantt (GanttTimelineService + HorizontalTimelineLayout)

- Pure layout in `Layout/HorizontalTimelineLayout`: `build($rows, $from, $to)`
  → `['day' => x_px, 'header' => [...]]` mapping; day width `$pxPerDay`
  (default 28px), row height 28px.
- `GanttTimelineService::buildTimeline($projectId, $options)` returns a
  structure that `GanttRenderer` turns into HTML. Services read repos via
  `DbConnectionInterface` only.
- Today marker: derived from FA `Today()` at render time (via injected
  `Clock`/`TodayProvider`? **No** — a `today()` callable is DI'd so tests
  pass a fixed date).

## Tab / page wiring

- Gantt view is served on the existing **Reports tab** (`?application=...&tab=reports`)
  as a sub-view (`view=gantt`) — **no new security area, no new menu item**,
  per "don't build a parallel silo". The ReportsTabController renders the
  gantt region above existing report blocks.
- Calendar emit happens automatically in `PmAppShell` post-init bootstrap
  (`registerCalendarEmitter`), plus from ProjectService/TaskService save
  hooks via `WorkflowHooksTrait` (`after_save`).

## Non-goals (v1)

- No JS drag-and-drop rescheduling (dates stay form-driven).
- No importing Calendar entries back into PM (read-only projector).

## Contracts

`PmCalendarEmitterInterface` (in PM):
```php
public function calendarEntries(string $entityType, ?string $entityId = null): array;
public function calendarEntriesQuery(array $criteria): array;
```
Implementations: `PmCalendarEmitter` (emits via hook, no Calendar DB writes).

`GanttTimelineServiceInterface`:
```php
public function buildTimeline(string $projectId, array $options = []): array;
```
