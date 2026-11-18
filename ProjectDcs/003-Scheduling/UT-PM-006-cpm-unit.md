# UT-PM-006 — Unit Test Specification — standalone CPM package

| | |
|---|---|
| ID | UT-PM-006 |
| Related | FR-PM-006-001..003; BR-PM-006 |
| Status | Approved |
| Harness | PHPUnit ^9.6 ^10, `tests/Unit`, stubs `tests/stubs.php` (+`tests/bootstrap.php`) |
| Amended | 2026-10-17 — case `001-004` corrected (FF), case `002-003` corrected (signed float), the "(≥0 only)" parenthetical dropped, and the service-level groups `004`/`005`/`006` added per `ID-SCHEME-DECISION.md` §3. |

The 20 cases of groups 001–003 are the original engine-only corpus. Groups
004–006 extend it: 004 and 005 are the FR-PM-006-002 / FR-PM-006-003 service
cases (which the 20 approved cases never described at all), and 006 holds the
defect regressions found while adjudicating them.

## UT-006-001 — CpmEngine::run forward pass
- **UT-PM-006-001-001** Two-task FS chain, dep A→B, A duration 8, B duration 5:
  A es=0,ef=8; B es=8,ef=13; project_duration=13.
- **UT-PM-006-001-002** FS + lag 3: B es=11,ef=16; duration 16.
- **UT-PM-006-001-003** SS edge A→B, lag 2: B es = A.es+2 = 2 (ef=7).
- **UT-PM-006-001-004** FF gates the successor's FINISH, so `EF_B >= EF_A + lag`.
  A(8), B(5), lag 2: `EF_B = 10`, therefore `ES_B = 10 − 5 = 5` (ef=10),
  project_duration=10, B critical. *(Corrected 2026-10-17: the original text
  read "B es = A.ef+2 = 10", which wrote the predecessor's finish candidate
  into the successor's start and was green-but-wrong. See `CPM-SPEC-REVIEW.md` §6.)*
- **UT-PM-006-001-005** SF: predecessor EF+lag−succ duration: B es = A.ef+lag−5.
- **UT-PM-006-001-006** Milestone zero duration: ef==es.
- **UT-PM-006-001-007** start_no_earlier_than anchor raises ES.
- **UT-PM-006-001-008** finish_no_earlier_than anchor raises EF.
- **UT-PM-006-001-009** Multiple-input merge: ES = max of incoming candidates.

## UT-006-002 — backward pass / slack / critical
- **UT-PM-006-002-001** LS/LF anchor: critical A-B chain both slack 0.
- **UT-PM-006-002-002** Non-critical merge task slack > 0.
- **UT-PM-006-002-003** slack = `ls−es` (TOTAL FLOAT, **signed**); critical iff
  `0 <= slack <= 0.5`. *(Corrected 2026-10-17: the original read
  `slack=abs(ls−es)`. `abs()` is a sign-convention slip — it contradicts
  `002-001` and `002-005` when read literally, and it would launder a
  constraint violation into positive float. See `CPM-SPEC-REVIEW.md` §2.)*
- **UT-PM-006-002-004** Milestone critical iff slack<=0.5.
- **UT-PM-006-002-005** `critical` array task ids + project_duration correct.

## UT-006-003 — robustness
- **UT-PM-006-003-001** Self-dependency rejected (predecessor==successor) → not cycle.
- **UT-PM-006-003-002** Cycle A→B→C→A: ok=false, `cycle` lists path [A,B,C,(A)].
- **UT-PM-006-003-003** Lag negative → recorded as-is (allowed; anchor math uses raw).
  *(This is the governing statement for lag sign; `001-002`'s original "(≥0 only)"
  parenthetical contradicted it and has been dropped. See `CPM-SPEC-REVIEW.md` §6.)*
- **UT-PM-006-003-004** Empty tasks → ok=true, duration 0, empty critical.
- **UT-PM-006-003-005** Constraint key variants: id/task_id, pred/successor,
  lag/lag_days, type default fs.
- **UT-PM-006-003-006** Missing predecessor/successor references skipped (not error).

## UT-006-004 — SchedulingService orchestration (FR-PM-006-002)
`SchedulingService::schedule(string $projectId)` loads its own task rows and
dependency edges through the injected repositories, so no case may pass a graph
in. The service is not loadable in-process (a wrong `use` spelling for
WorkflowHooksTrait fatals), so every case runs in a child process.

- **UT-PM-006-004-001** Computed ES/EF/LS/LF/slack/critical + project duration
  are persisted through the injected repository; one UPDATE per task row; no
  direct FA calls.
- **UT-PM-006-004-002** `pm_schedule_schedule_after` then
  `pm_schedule_schedule_saved` fire, in that order, each exactly once.
- **UT-PM-006-004-003** On `ok=false` (a cycle): return early, no hook fired,
  no scheduling field written.
- **UT-PM-006-004-004** `schedule()` takes exactly one parameter,
  `string $projectId`.
- **UT-PM-006-004-005** `estimated_hours` is converted to duration days at
  8h/day (20h → 3 days).
- **UT-PM-006-004-006** Both hook payloads carry `project_id`, `ok`, `duration`,
  `critical[]` and the retained `result`; only the `_saved` payload carries
  `persisted => true`.

## UT-006-005 — TaskDependencyService guards (FR-PM-006-003)
`TaskDependencyService::add()` validates, refuses duplicates, refuses edges
that would close a cycle, and only then writes.

- **UT-PM-006-005-001** Self dependency (`predecessor_id == task_id`) is
  rejected by validation, before any read or write.
- **UT-PM-006-005-002** An edge that would close a cycle is rejected and
  nothing is written. Reads are expected — the guard must see the persisted
  graph — so the assertion is on writes, not on "no SQL ran".
- **UT-PM-006-005-003** An acyclic edge in an acyclic graph persists and
  returns the new dependency id.
- **UT-PM-006-005-004** `deleteAllFor` issues one DELETE covering every edge of
  the task.
- **UT-PM-006-005-005** `CpmEngine::detectCycle()` and
  `Engine\TaskDependencyGraph` both exist and are reachable from the guard.
- **UT-PM-006-005-006** `TaskDependencyRepository::all()` exists — it is the
  row scan the guard reads.
- **UT-PM-006-005-007** `TaskDependencyService` is loadable at all.
- **UT-PM-006-005-008** The cycle refusal message names the cycle as a CLOSED
  traversal, so the first node is repeated last.

## UT-006-006 — CpmEngine regressions found while adjudicating 001–003
- **UT-PM-006-006-001** The `cycle` payload names only the nodes actually on a
  loop; an acyclic task hanging off one is not reported.
- **UT-PM-006-006-002** A `Y-m-d` constraint anchor is a schedule offset, not
  days-since-epoch.
- **UT-PM-006-006-003** `detectCycle()` returns a genuine closed cycle path
  naming only the loop's own nodes.
- **UT-PM-006-006-004** `detectCycle()` returns `[]` for an acyclic graph.