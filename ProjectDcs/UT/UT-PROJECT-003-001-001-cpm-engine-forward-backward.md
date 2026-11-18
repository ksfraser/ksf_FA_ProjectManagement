# UT-PROJECT-003-001-001 — CPM Engine Forward + Backward Pass

**Under test:** `CpmEngine` (ARCH-PROJECT-003)
**BABOK:** Requirements Analysis → Validate
**Traceability:** BR-PROJECT-003; FR-PROJECT-003-001; FR-PROJECT-003-002

## Test Cases

### UT-PROJECT-003-001-001-001 — forward pass: serial chain

3 tasks A→B→C, duration 2/3/1, lag 0.
**Assert:** `ES(A)=day0, EF=day2; ES(B)=day2, EF=day5; ES(C)=day5, EF=day6`.
Project `duration=6`.

### UT-PROJECT-003-001-001-002 — forward pass: multi-predecessor max (merge)

C waits on A(EF=2) and B(EF=7). **Assert:** `ES(C)=7`.

### UT-PROJECT-003-001-001-003 — backward pass + slack (merge)

Merge graph above. **Assert:** `LS(C)=LF-dur`;
`critical=true` on the A/C or B/C path with slack 0; non-critical branch has
`slack > 0`.

### UT-PROJECT-003-001-001-004 — lag propagation (SS + lag)

`ss` dep with lag 2 between A(start 0) and B. **Assert:** `ES(B)=2`.

### UT-PROJECT-003-001-001-005 — zero-duration anchor (milestone)

Milestone in the middle of a chain. **Assert:** it anchors → no duration
shift; `isMilestone` rows carry ES=EF and don't inflate the project span.

### UT-PROJECT-003-001-001-006 — constraint overrides earliest

Task C has `constraint type=start_no_earlier_than, date=day9`, but CPM earliest
is day5. **Assert:** scheduled `ES=9`, `EF=ES+duration`; constraint honoured.

### UT-PROJECT-003-001-001-007 — cycle detection (no hang)

Self-loop + mutual A↔B. **Assert:** engine returns `ok=false` with
`cycle=[...]` path, completes — never infinite-loops.

### UT-PROJECT-003-001-001-008 — float/critical tolerance

`critical` when `|slack| <= 0.5d` (float tolerance). **Assert:** slack 0.4 →
critical, slack 0.7 → not.

## Setup

Pure PHP, no FA/PDO needed: the engine takes task/dep rows as arrays.
Use intervals in day-units from a fixed epoch.
