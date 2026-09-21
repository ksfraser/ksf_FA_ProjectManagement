# UC-PM-007-001 — Project manager closes a task meeting; members get time, outsiders excluded

@BABOK Related: BR-007; FR-PM-007-001/002; UC-CAL-007-001 (anchor close).
Status: Approved — BABOK; implementation parks next stage.
Module: ksf_FA_ProjectManagement (responder + task-close subscriber).

## Preconditions
- Task-bearing event (project P, task T), attendees: 3 assignees of P + 2
  supporting contractors. Actor = project manager (has close privilege).

## Main flow
1. Actor closes the entry (UC-CAL-007-001); Calendar commits + broadcasts.
2. Timesheets subscriber asks membership; PM responder resolves the 3 assignee
   emails (identity bridge users.email → assignments.employee_id) and returns
   them; the 2 contractors are unclassified.
3. Bulk form: 3 members pre-checked; contractors excluded from time on T.
4. PM subscriber (FR-PM-007-002) seals task T's time window to the recorded
   event window.
5. Contractors may still attach expenses (FR-EXPENSE-007-001) and later file
   their own time against their own project (FR-TIME-007-003).

## Alternate flows
- **3a. Assignment mismatch:** a "member" attendee email isn't in `users`/the
  assignment -> not classified; excluded. Task time still closes.
- **4a. Task already sealed:** repeat close -> no-op.

## Postconditions
- Members auto-timed; outsiders untimed against T but eligible for expense;
  task window sealed; no cross-table writes.

## Acceptance
- ARI: exactly the 3 assignees auto-timed; contractors untimed on T but
  expense-eligible; task T shows closed window.