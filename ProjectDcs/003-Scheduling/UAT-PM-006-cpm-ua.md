# UAT-PM-006 — User Acceptance — CPM schedule UI (module preview)

| | |
|---|---|
| ID | UAT-PM-006 |
| Related | BR-PM-006, UT-PM-006 |
| Env | UAT-latest (ksf_FA_ProjectManagement) — FA 2.4.19, shipped module, real MySQL |

## UAT-006-001 — create schedule from project
- Steps: Open Project → Schedule tab → "Generate" (durations + dependencies).
- Pass: forward/backward passes give ES/EF/LS/LF/slack/critical columns; the
  critical path is highlighted (≤0.5d slack); project_duration shown.
- Verify a milestone row shows duration 0 and ef==es.

## UAT-006-002 — edit milestone/constraint
- Set finish_no_earlier_than on a task and re-Generate: EF reflects anchor;
  critical set recomputes.

## UAT-006-003 — cycle rejection UX
- Steps: Add dependency that would close a cycle; save.
- Pass: no write; UI shows cycle path message (ok=false) without exception.

## UAT-006-004 — data isolation
- Confirm no FA `db_*` calls, no hooks fired from CpmEngine itself; transport
  and hooks only via SchedulingService/Repository at the FA boundary.
