# FR-PM-006-001 — CpmEngine: precedence-graph schedule computation

| | |
|---|---|
| ID | FR-PM-006-001 |
| Related | BR-PM-006, BR-003; UT-PM-006-* |
| Status | Approved (package) |
| Amended | 2026-10-17 — FF corrected and criticality made one-sided; see `CPM-SPEC-REVIEW.md` §2 and §6. Acceptance ids aligned per `ID-SCHEME-DECISION.md` §4. |

### Requirement
Provide a pure `CpmEngine::run(array $tasks, array $dependencies, array $options = []): array`
computing a full CPM schedule for precedence graphs with edge types
FS/SS/FF/SF, integer lag days, zero-duration milestones, and hard
start/finish no-earlier/later-than anchor constraints.

It returns `{ok, tasks, project_duration, critical}` or, on a precedence
cycle, `{ok: false, cycle}` — never an exception.

### Relation types

Forward pass — for every task, `ES` is the MAX of the candidate offered by each
predecessor, then clamped up by any "no earlier than" anchor:

| Relation | Gates | Candidate written to `ES_succ` |
|---|---|---|
| FS | successor **start** | `EF_pred + lag` |
| SS | successor start | `ES_pred + lag` |
| FF | successor **finish** | `EF_pred + lag − dur_succ` |
| SF | successor start | `EF_pred + lag − dur_succ` |

**FF gates the successor's FINISH**, not its start. `FF(i→j)` means
`EF_j ≥ EF_i + lag`; since `EF_j = ES_j + dur_j`, the earliest start that
satisfies it is `ES_j = EF_i + lag − dur_j`. Writing `EF_i + lag` straight into
`ES_j` constrains a field FF never gates, and pushes the successor a full
duration later than the relation requires. dotProject, OpenProject and
LibreProject all implement FF against the successor's finish.

Backward pass — for every task, `LF` is the MIN of the bound contributed by each
successor:

| Relation | Bound |
|---|---|
| FS | `LF_i ≤ LS_j − lag` |
| SS | `LF_i ≤ LS_j − lag + dur_i` |
| FF | `LF_i ≤ LF_j − lag` |
| SF | `LF_i ≤ LF_j − lag + dur_i` |

### Float and criticality

`slack` is **total float**, `LS − ES`, and it is **signed**. It is
non-negative for every task of a well-formed acyclic network; the one way it
goes negative is a "no later than" anchor earlier than the computed schedule,
which is a constraint violation the caller must see — not float. Taking an
absolute value would launder that violation into a healthy-looking positive
number *and* hide it from criticality, at exactly the moment it matters.

Criticality is a **one-sided band**: `0 <= slack <= 0.5` (`CRITICAL_TOLERANCE`).
The absolute value is used for the band only in the sense that the band has
width; a task with negative float is excluded.

### Acceptance

Per `ID-SCHEME-DECISION.md` §4 these cite the canonical UT ids.

- **UT-PM-006-001-001** forward pass ES/EF for FS(+lag) chain.
- **UT-PM-006-001-002** FS edge honours lag 3 → `ES = EF_pred + 3`.
- **UT-PM-006-001-003** SS edge via predecessor ES+lag.
- **UT-PM-006-001-004** FF edge gates the successor's finish: `ES_succ = EF_pred + lag − dur_succ`.
- **UT-PM-006-001-005** SF edge via predecessor EF+lag−duration.
- **UT-PM-006-001-006** milestone EF=ES (zero duration).
- **UT-PM-006-001-007** `start_no_earlier_than` anchor raises ES.
- **UT-PM-006-001-008** `finish_no_earlier_than` anchor raises EF.
- **UT-PM-006-001-009** multiple-input merge takes the max of incoming candidates.
- **UT-PM-006-002-001** backward pass LF/LS; critical chain has zero total float.
- **UT-PM-006-002-002** non-critical merge task has float > 0.
- **UT-PM-006-002-003** total float and criticality tolerance.
- **UT-PM-006-002-004** milestone critical iff slack <= 0.5.
- **UT-PM-006-002-005** `critical` array task ids + project_duration correct.
- **UT-PM-006-003-001** self-dependency rejected, and it is NOT a cycle.
- **UT-PM-006-003-002** cycle → `ok=false` + cycle path (closed traversal), no throw.
- **UT-PM-006-003-003** negative lag used raw.
- **UT-PM-006-003-004** empty task set → `ok=true`, duration 0, empty critical.
- **UT-PM-006-003-005** constraint key variants; `type` defaults to `fs`.
- **UT-PM-006-003-006** missing predecessor/successor references skipped, not an error.
- **UT-PM-006-006-001** the cycle report names only the nodes actually on a loop.
- **UT-PM-006-006-002** a calendar-date anchor is a schedule offset, not days-since-epoch.