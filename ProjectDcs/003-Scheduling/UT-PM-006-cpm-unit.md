# UT-PM-006 — Unit Test Specification — standalone CPM package

| | |
|---|---|
| ID | UT-PM-006 |
| Related | FR-PM-006-001..003; BR-PM-006 |
| Status | Approved |
| Harness | PHPUnit ^9.6 ^10, `tests/Unit`, stubs `tests/stubs.php` (+`tests/bootstrap.php`) |

## UT-006-001 — CpmEngine::run forward pass
- **UT-PM-006-001-001** Two-task FS chain, dep A→B, A duration 8, B duration 5:
  A es=0,ef=8; B es=8,ef=13; project_duration=13.
- **UT-PM-006-001-002** FS + lag 3 (≥0 only): B es=11,ef=16; duration 16.
- **UT-PM-006-001-003** SS edge A→B, lag 2: B es = A.es+2 = 2 (ef=7).
- **UT-PM-006-001-004** FF: predecessor EF+lag: B es = A.ef+2 = 10.
- **UT-PM-006-001-005** SF: predecessor EF+lag−succ duration: B es = A.ef+lag−5.
- **UT-PM-006-001-006** Milestone zero duration: ef==es.
- **UT-PM-006-001-007** start_no_earlier_than anchor raises ES.
- **UT-PM-006-001-008** finish_no_earlier_than anchor raises EF.
- **UT-PM-006-001-009** Multiple-input merge: ES = max of incoming candidates.

## UT-006-002 — backward pass / slack / critical
- **UT-PM-006-002-001** LS/LF anchor: critical A-B chain both slack 0.
- **UT-PM-006-002-002** Non-critical merge task slack > 0.
- **UT-PM-006-002-003** slack=abs(ls−es); critical=slack<=0.5 (tolerance).
- **UT-PM-006-002-004** Milestone critical iff slack<=0.5.
- **UT-PM-006-002-005** `critical` array task ids + project_duration correct.

## UT-006-003 — robustness
- **UT-PM-006-003-001** Self-dependency rejected (predecessor==successor) → not cycle.
- **UT-PM-006-003-002** Cycle A→B→C→A: ok=false, `cycle` lists path [A,B,C,(A)].
- **UT-PM-006-003-003** Lag negative → recorded as-is (allowed; anchor math uses raw).
- **UT-PM-006-003-004** Empty tasks → ok=true, duration 0, empty critical.
- **UT-PM-006-003-005** Constraint key variants: id/task_id, pred/successor,
  lag/lag_days, type default fs.
- **UT-PM-006-003-006** Missing predecessor/successor references skipped (not error).
