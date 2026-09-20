# FR-PROJECT-005-002 — Gantt Visualization

**Parent:** BR-PROJECT-005
**Status:** Proposed
**Traceability:** ARCH-PROJECT-005; UT-PROJECT-005-002-*

## Functional Requirement

Render the scheduled plan as a **Gantt timeline** from CPM output
(FR-PROJECT-003) and native FA files (FR-PROJECT-004): task bars, milestone
diamonds, dependency arrows, date grid, today-marker, critical-path
highlighting.

### FR-PROJECT-005-002-001 — Data source

`GanttTimelineService` reads:

- `tasks` (id, name, parent_task_id, is_milestone, start/end from CPM
  ES/EF/IS/IF persist, status, progress, constraint cols),
- `0_fa_pm_task_dependencies` rows,
- `0_fa_pm_files` counts for any task/project (badge: paperclip + N).

Pure layout (days→pixels) lives in `Layout/HorizontalTimelineLayout` so the
geometry is testable without FA and without DOM.

### FR-PROJECT-005-002-002 — Rendering

`GanttRenderer` returns server-side HTML (nested `<table>` grid + absolutely
positioned bar divs inside each row-cell). No JS asset is required: CSS
classes (`pm-gantt-row`, `pm-gantt-bar`, `pm-gantt-milestone`,
`pm-gantt-critical`, `pm-gantt-today`, `pm-gantt-link`) style bars; a `.n`
grid shows week + weekday headers. Non-fatal if a task has NULL dates — row
renders greyed "unscheduled".

Deliverables: `src/.../Service/GanttTimelineService.php`,
`src/.../Layout/HorizontalTimelineLayout.php`,
`src/.../View/GanttRenderer.php`, `assets/ksf_pm/gantt.css`

### FR-PROJECT-005-002-003 — Interactivity (read-only, minimal)

- Hover tooltip: `name — MM/DD→MM/DD — progress% (+% behind/ahead)`.
- Click on a bar → navigates to the task's edit in the Tasks tab
  (`?action=edit_task&id=...`).
- No drag-drop re-scheduling in v1 (dates are edited in the Tasks form, per
  BR-PROJECT-003 scope).

## Acceptance Criteria

1. A 3-task serial chain renders 3 bars + 2 dependency arrows; critical
   chain bars get `pm-gantt-critical`.
2. A milestone (is_milestone=1) renders as a diamond, not a bar.
3. Tasks with NULL dates render "unscheduled", never fatal.
4. Rows with files show the paperclip count badge.
