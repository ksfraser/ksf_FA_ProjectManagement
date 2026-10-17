# FR-PM-007-002 — Task time-window close on `ksf_event_closed`

@BABOK Related: BR-007 (PM subscriber: "closes the linked task's logged time");
          FR-CAL-007-002 (broadcast it consumes).
@UML : PM/subscriber -> on `ksf_event_closed` with task_id -> close task time window
Status: Approved — BABOK; implementation parks next stage.
Module: ksf_FA_ProjectManagement (owner of 0_fa_pm_tasks / 0_fa_pm_task_progress writes).

## Need (BABOK What-not-How)
When a task-bearing meeting closes, the task's logged-time/actual-hours window
should be sealed so later edits don't drift from what the calendar recorded —
the task's time portion is CLOSED, regardless of what Timesheets created.

## Requirement
1. On `ksf_event_closed` where the DTO's `task_id` matches a row in
   `0_fa_pm_tasks`:
   - the task's time window is marked closed (status/flag) and the recorded
     worked window (`started_at`..`closed_at`) is snapshotted to the task's
     `actual_hours` delta or a `0_fa_pm_task_progress` row, via the module's own
     TaskService/TaskRepository (FaDbAdapter → native `db_*`).
2. Idempotent: a repeat broadcast for the same task is a no-op (nothing
   re-written, no window re-open).
3. The module writes ONLY its own tables; it does not create or touch
   timesheet/expense rows.
4. Subscriber is fault-tolerant: failure here must not break other listeners of
   the same broadcast.

## Acceptance
- ARI: task-bearing close -> the task's window shows closed once; re-broadcast
  -> unchanged.
- AZZ: DTO `task_id` unknown to this module -> no-op, no error.
- BON: window correctly equals `closed_at - started_at` recorded hours.