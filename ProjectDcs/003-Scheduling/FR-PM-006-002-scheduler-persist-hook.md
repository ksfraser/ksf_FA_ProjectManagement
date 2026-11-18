# FR-PM-006-002 — Scheduler: transport-agnostic schedule persistence

| | |
|---|---|
| ID | FR-PM-006-002 |
| Related | BR-PM-006; UT-PM-006-004-* |
| Status | Approved (package) |
| Amended | 2026-10-17 — §"Signature", §"Hooks" and §"Hook payload" corrected against the shipped code; see the rulings below. Acceptance ids renumbered per `ID-SCHEME-DECISION.md` §3. |

### Requirement
`SchedulingService::schedule(string $projectId): array` must load the
project's task rows and precedence edges through its injected repositories, run
the engine, persist per-task ES/EF/LS/LF/slack/critical + project duration +
critical set, and fire workflow hooks through the module's WorkflowHooksTrait
registration `registerWorkflowType('schedule', 'pm_schedule')`.

### Signature

```php
public function schedule(string $projectId): array
```

**This deviates from the original draft of this requirement**, which read
`schedule($projectId, $startDate, $tasks, $dependencies[, $endDate])`. The
deviation is deliberate and is now the requirement.

Rationale: `schedule()` reads its own inputs from the persisted graph. The
drafted form let a caller hand in a `$tasks` array that had already drifted
from the database — the schedule would then be computed over a graph no other
part of the system believes in, and the ES/EF/LS/LF persisted by this call
would silently disagree with the task list the next page load reads. Loading
from the repository makes the persisted graph the single source of truth and
makes a stale schedule unrepresentable.

Consequence: the draft's `$endDate` acceptance item is withdrawn. There is no
`$endDate` parameter to default, so there is nothing to verify.

### Hooks

Two hooks fire, in this order, both under the `pm_schedule` prefix:

| Hook | When | `persisted` |
|---|---|---|
| `pm_schedule_schedule_after` | the schedule is COMPUTED, nothing written | absent |
| `pm_schedule_schedule_saved` | the computed schedule has been WRITTEN | `true` |

**This also deviates from the original draft**, which required a single
`schedule_saved` under prefix `pm_sched`.

Rationale: separating "computed" from "persisted" is what lets a caller render
a schedule preview, or explain a schedule to a user, without writing to the
database. With one post-write hook, a read-only listener has to pay for writes
it did not ask for, or the service needs a second "do not persist" flag — which
is a worse contract than a second hook. The `pm_schedule` prefix (not
`pm_sched`) matches `registerWorkflowType('schedule', 'pm_schedule')` and every
other record type in the module.

### Hook payload

Both hooks receive the same array:

| Key | Type | Meaning |
|---|---|---|
| `project_id` | string | the project that was scheduled |
| `result` | array | the engine's full return: `{ok, tasks, project_duration, critical}` |
| `ok` | bool | true on both hooks (neither fires on the `ok=false` path) |
| `duration` | int | project duration in days — surfaced from `result.project_duration` |
| `critical` | string[] | the critical-path task set — surfaced from `result.critical` |
| `persisted` | bool | `true` on `schedule_saved` only |

`result` is **retained**, not replaced: `duration` and `critical` were added
alongside it rather than substituted for it.

Rationale: `duration` and `critical[]` are what let a consumer explain WHY a
date moved. That is the entire point of critical-path analysis in a project
management product — dotProject and OpenProject both surface the critical-path
task set and the float/criticality data in their scheduling output — and a
listener that has to walk the whole per-task `tasks` table inside `result` to
find those two fields will not bother. `result` stays for the caller that
genuinely needs per-task ES/EF/LS/LF.

### Acceptance

Per `ID-SCHEME-DECISION.md` §2 the UT scheme is canonical and this document
cites UT ids rather than minting its own.

- **UT-PM-006-004-001** persists results via injected repository (no FA calls).
- **UT-PM-006-004-002** fires `pm_schedule_schedule_after` then
  `pm_schedule_schedule_saved`, each exactly once.
- **UT-PM-006-004-003** ok=false path returns early, no hook fired, nothing written.
- **UT-PM-006-004-004** `schedule(string $projectId)` is the whole signature.
- **UT-PM-006-004-005** `estimated_hours` converted to duration days (8h/day).
- **UT-PM-006-004-006** hook payload carries `project_id`, `duration`,
  `critical[]`, `ok`, `result` and `persisted`.