# FR-PROJECT-005-001 — PM Calendar Feed (Emitter)

**Parent:** BR-PROJECT-005
**Status:** Proposed
**BABOK Direction:** Requirements Analysis → Specify & Model
**Traceability:** ARCH-VS-PM-005; UT-PROJECT-005-001-*

## Functional Requirement

Expose PM schedule dates to the FA Calendar as entries, without PM knowing the
Calendar implementation — transport-agnostic, hook-driven; single source of
truth = CPM output (FR-PROJECT-003-001).

### FR-PROJECT-005-001-001 — Entry model

Each project/task/milestone row that carries a date maps to a calendar entry:
- **Project** → span entry `[start_date, end_date]` (type `project`), color/cal
  source from ProjectTypes.
- **Milestone (is_milestone=1)** → **point entry** on `constraint_date`
  (else `end_date`), type `milestone`, zero duration.
- **Task** → span entry `[starts_on, ends_on]` from the CPM ES/EF schedule
  (when computed), else the raw `start_date/end_date`.
- **Assignment** → span entry `[assignment.start_date, assignment.end_date]`
  for the employee.

All entries carry `entity_type='fa_pm_*'` and `entity_id` so the Calendar can
deep-link back to the PM record (FR-PROJECT-001-005 integration).

### FR-PROJECT-005-001-002 — Emit protocol

PM is an **emitter** (not listener) over the calendar protocol. On
create/update/delete of a project, task, milestone or assignment, PM calls:

```
hook_invoke_first('calendar_entry_upsert', $data)
hook_invoke_first('calendar_entry_delete', $data)
```

where `$data = [
  'source'   => 'ksf_FA_ProjectManagement',
  'subject'  => $subject,
  'start'    => $startDate,   // FA Y-m-d
  'end'      => $endDate,     // inclusive, Y-m-d
  'all_day'  => true,
  'type'     => 'project|milestone|task|assignment',
  'entity_type' => 'fa_pm_project'|'fa_pm_task'|'fa_pm_assignment',
  'entity_id'   => $id,
  'project_id'  => $projectId,
  'calendar'    => $calendarId ?? null,   // optional target calendar
  'notes'       => $description ?? null,
]`
```

The emitter service is `PmCalendarEmitter` (implements a small emitter
contract) DI'd through the PM container. Calendar module's existing
`calendar_entry_*` listeners consume it (those listeners already exist in
`ksf_FA_Calendar/hooks.php`).

### FR-PROJECT-005-001-003 — No-crash guarantee

If no Calendar listener is active (Calendar module not installed/activated),
`hook_invoke_first` returns `null` and PM continues — emitting is best-effort,
never throws, never blocks the save.

### FR-PROJECT-005-001-004 — Idempotency key

`entity_type + entity_id + type` is the idempotency key for upsert; a
re-emit replaces, never duplicates.

## Acceptance Criteria

1. Creating a task with dates emits exactly one calendar upsert with
   `entity_type=fa_pm_task`.
2. Deleting a project emits a calendar delete for that project type only.
3. With no calendar module present, save still succeeds (null hook result).
