# FR-PM-007-001 — Membership responder for project-task meetings

@BABOK Related: BR-007; FR-CAL-007-003 (classification protocol responder side);
          feeds FR-TIME-007-002/003 (member vs external partition).
@UML : PM/responder -> `ksf_event_classify_attendees` when DTO carries project_id/task_id
Status: Approved — BABOK; implementation parks next stage.
Module: ksf_FA_ProjectManagement (answers membership for ITS track; read-only).

## Need (BABOK What-not-How)
Only project TEAM MEMBERS may be auto-timed when a task meeting closes.
ProjectManagement owns the assignment truth
(`0_fa_pm_assignments` `{project_id, employee_id}`), so it must answer which
attendee emails are members — so Timesheets never has to guess or read
assignment tables itself.

## Requirement
1. When a subscriber issues
   `hook_invoke_all('ksf_event_classify_attendees', $dto, $opts)` and the DTO
   carries a `task_id` (or `project_id`) this module recognizes, it responds by
   appending to `$opts['classification']['member']` every
   `attendee_emails[]` entry that resolves to a team member:
   `email → users.email → users.user_id → 0_fa_pm_assignments.employee_id`
   for the event's project (task implied when `task_id` matches a row in
   `0_fa_pm_tasks`).
2. Attendees NOT resolvable to an assignee are NOT listed as member — they
   remain unclassified/external (the caller's aggregation decides).
3. Service uses the module's OWN DAO layer (Dictionary/Schema +
   DbConnectionInterface via FaDbAdapter at runtime) — read-only, native `db_*`.
4. Responder is fault-tolerant: an exception is contained; never aborts the
   caller's classification.
5. Responder never writes during classification.

## Acceptance
- ARI: task project with assignments {U1,U2} and attendee emails resolving to
  U1,U3 -> member list = [U1]; U3 unclassified.
- AZZ: DTO with no project_id/task_id -> no response (no-op for this module).
- BON: identity bridge miss (email not in `users`) -> not classified member.
- CAN: responder DB error -> caller continues with other responders.