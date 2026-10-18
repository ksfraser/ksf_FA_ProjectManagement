# CPM-SPEC-REVIEW — adjudication of the five failing UT-PM-006 cases

| | |
|---|---|
| ID | CPM-SPEC-REVIEW |
| Date | 2026-10-17 |
| Scope | The five red cases of `tests/Unit/CpmEngineTest.php` (the 20 approved cases of `UT-PM-006-cpm-unit.md`), plus three adjacent findings the corpus does not reach |
| Inputs | `CpmEngine.php` at `23f9b4a`, the PHPUnit run of 2026-10-17, standard CPM semantics, and dotProject / OpenProject / LibreProject behaviour |
| Ruling owner | Pending — §2 and §6 need the requirement owner's sign-off before the spec text is edited |

## 0. How to read the rulings

Two rules were applied to every case:

1. **Standard CPM wins over the code**, because the module's stated purpose is
   "a real critical-path analysis" and every established product (dotProject,
   OpenProject, LibreProject, MS Project) implements textbook CPM.
2. **The engine may not launder a wrong answer.** Where the spec is itself
   wrong, the ruling says so and the test stays red rather than being bent to
   match the implementation.

One naming correction before the table: the failing cases were reported under
ids `-003-002` (abs slack), `-002-003` (critical set) and `-003-003` (cycle
path). The ids actually in the spec and the suite are `UT-PM-006-002-003`,
`UT-PM-006-002-005` and `UT-PM-006-003-002`; `-003-003` is the negative-lag
case and passes. This is instance #4 of the id collision adjudicated in
`ID-SCHEME-DECISION.md`.

| # | Case | Ruling |
|---|---|---|
| 1 | `UT-PM-006-002-001` critical chain has zero slack | **Code wrong** (fixed) |
| 2 | `UT-PM-006-002-003` `slack = abs(ls-es)` | **Spec wrong** (test left red, in fact green by construction — see §2) |
| 3 | `UT-PM-006-002-005` critical set `['A','B']` | **Code wrong** (fixed, same root cause as #1) |
| 4 | `UT-PM-006-003-001` self-dependency is not a cycle | **Spec right, code wrong** (fixed) |
| 5 | `UT-PM-006-003-002` cycle path `[A,B,C,(A)]` | **Spec right, code wrong** (fixed) |

---

## 1. `UT-PM-006-002-001` — "LS/LF anchor: critical A-B chain both slack 0"

**Spec says.** For A(8) → B(5), FS: A `lf=8`, `ls=0`, `slack=0`, critical;
B `lf=13`, `ls=8`, `slack=0`, critical.

**Code did.** A `lf=0`, `ls=-8`, `slack=-8`, **not** critical.

**Standard CPM says.** B has no successor, so `LF_B = project finish = 13`
and `LS_B = 13 - 5 = 8`. The single FS edge A→B means A must finish by the
time B may start: `LF_A = LS_B - lag = 8`, `LS_A = 8 - 8 = 0`. Total float
`A = LS_A - ES_A = 0 - 0 = 0`. Both tasks are critical, and `project_duration`
is 13. This is the textbook two-task chain.

**Why the code disagreed.** `CpmEngine.php:183` (pre-fix) computed the correct
bound on line 182 (`$cand = $byId[$m]['ls'] - $lag;`) and then immediately
overwrote it with `$cand = $byId[$m]['es'] - $byId[$n]['duration'] - $lag;`.
That second expression is not a backward-pass rule at all — it references the
successor's **earliest** start and the predecessor's duration, so every FS
predecessor is pinned one duration earlier than it can be. It also silently
ignores the successor's own float.

**Ruling: the spec is right, the code is wrong.** Fixed.

**Corrected expectation** — unchanged from the spec:
`A lf=8 ls=0 slack=0 critical`, `B lf=13 ls=8 slack=0 critical`, `duration=13`.

**Scale of the defect.** On the five-task network of §7, HEAD reports
`critical = {2, 5}` — the *short* path — and gives the genuinely critical tasks
1, 3 and 4 a float of −3, −4 and −5. Negative float in an acyclic network with
no deadline is impossible under standard CPM, and is the tell-tale of this bug.

---

## 2. `UT-PM-006-002-003` — "`slack=abs(ls-es)`; `critical=slack<=0.5`"

**Spec says.** The reported slack of a task is the **absolute** value of
`LS - ES`, and criticality is `slack <= 0.5`.

**Code does.** `CpmEngine.php:221` (unchanged by this work): `slack = LS - ES`,
signed; `:222` criticality via `abs(slack) <= 0.5`.

**Standard CPM says.** The quantity is **total float**, defined `TF = LS - ES`
and *not* absolute:

- Total float is non-negative for every task of a well-formed acyclic
  network — that is what makes it "float". The backward pass can only ever
  place a task later than the forward pass placed it (`LS >= ES`).
- The one way `TF` goes negative is a "no later than" (deadline) constraint
  earlier than the computed schedule. In that situation the task has a
  **constraint violation**, not float. MS Project surfaces this as negative
  total slack; dotProject and OpenProject both refuse/mark the schedule rather
  than reporting a positive number. `abs()` would convert a −3d violation into
  a healthy-looking +3d of float, and would then *also* hide it from
  criticality — the defect would be invisible at exactly the moment it matters.
- LibreProject / dotProject compute total float as the signed late-minus-early
  difference and only ever use an absolute value for the *criticality
  tolerance band*, i.e. `TF <= tolerance`, never `|TF| <= tolerance`.

**The spec is also self-contradictory here.** Read `abs()` literally against
its own two sibling cases: with the (buggy) `A ls=-8`, `abs()` yields slack 8,
so A is *not* critical — which contradicts `UT-PM-006-002-001` (A has slack 0)
and `UT-PM-006-002-005` (critical is `['A','B']`). `abs()` cannot be a real
rule here; it is a sign-convention slip in the spec text.

**Ruling: the spec is wrong. The code is right.** No production change to the
slack value itself. The expected `abs(ls - es)` in the test remains green
*because* after the §1 fix `LS >= ES` for every task, so `abs(LS-ES) == LS-ES`;
the equality holds trivially and the assertion is not weakened by anything done
here. The **spec text must be corrected** to `slack = LS - ES` (total float,
signed) and `critical = 0 <= slack <= tolerance`.

**Corrected expectation (post-ruling):**
`slack == ls - es` for every task, and `critical == (slack >= 0 && slack <= 0.5)`.

**Production change applied at the same time (see §2.1 in the code-fix table
below).** Criticality is now a one-sided band, `slack >= 0 && slack <= 0.5`,
instead of `abs(slack) <= 0.5`. With float non-negative the two are
equivalent, so no test result changes; the difference appears only for a
deadline violation, where `abs()` would have promoted the violating task to
critical.

---

## 3. `UT-PM-006-002-005` — "critical array task ids + project_duration"

**Spec says.** For the A(8)→B(5) FS chain, `critical = ['A','B']`,
`project_duration = 13`.

**Code did.** `critical = ['B']`.

**Standard CPM says.** A task is critical iff its total float is zero, and the
critical path of any acyclic network is non-empty (the longest path always has
float 0). For the two-task chain, A and B are both on the longest path, so the
critical set is `{A, B}`. Every established product agrees: dotProject's task
list "critical" filter marks a two-link chain end to end.

**Why the code disagreed.** Identical root cause to §1. A's float came out
negative (−8), and with `abs(slack) <= 0.5` a negative float fails the
criticality test, so A silently dropped off the critical path while B stayed
on it. This is the most damaging symptom of the bug: the engine reported a
critical path that is not a path through the network's longest chain.

**Ruling: the spec is right, the code is wrong.** Fixed by the §1 change.

**Corrected expectation** — unchanged from the spec: `critical == ['A','B']`,
`project_duration == 13`.

---

## 4. `UT-PM-006-003-001` — "Self-dependency rejected (predecessor==successor) → not cycle"

**Spec says.** A self edge is rejected, and it is **not** reported as a cycle.

**Code did.** `['ok' => false, 'cycle' => ['A','A']]`.

**Standard CPM says.** The engine must not throw (BR-PM-006 §5), so `ok=false`
is right. Whether the self edge is a "cycle" is a question about *which layer*
owns the diagnosis:

- A self edge is a length-1 loop, so a pure graph algorithm will always find it.
- But in every established product the edge never reaches the scheduler: it is
  refused by input validation. dotProject's `dep_validate` rejects a
  self-dependency outright ("a task cannot depend on itself"); OpenProject's
  relation validation does the same. There is no "cycle" error to show the
  user, because the user never got as far as building a graph.
- So the spec's "→ not cycle" is the better contract: it separates
  *validation errors* (self reference, unknown task ids, bad relation type)
  from *precedence cycles* (two or more tasks in a loop). Conflating them means
  a caller cannot tell "you typed nonsense" from "your plan has a loop".

**Ruling: the spec is right, the code is wrong.** `CpmEngine.php:58-60` now
returns `['ok' => false, 'cycle' => []]` — rejected, and no cycle path, because
there is no cycle to report. The human-readable reason
("A task cannot depend on itself.") is `TaskDependencyService::validate()`'s
job, which already exists at `TaskDependencyService.php:118-128`.

**Corrected expectation** — unchanged from the spec: `ok === false`,
`cycle === []`.

---

## 5. `UT-PM-006-003-002` — "Cycle A→B→C→A: ok=false, `cycle` lists path [A,B,C,(A)]"

**Spec says.** `ok=false` and `cycle` is the traversal that closes back on its
first node: `[A,B,C,A]`.

**Code did.** `ok=false`, `cycle = ['A','B','C']` — the three nodes on the
loop, without the closing node.

**Standard CPM says.** `ok=false` and "a cycle path" are both required by
BR-PM-006 §5. The only question is whether the payload repeats the entry node.
Repeating it is a *display convention*, not a different graph: it is what makes
the path readable as a loop (`A → B → C → A`) in a UI, and it is what a cycle
error message has to show for the user to see where the loop closes. OpenProject reports a circular
relation as the chain of offending relations, and a chain that starts and ends
on the same work item is the only form in which the user can see where the loop
closes. The `(A)` in the spec is unambiguous about the intent.

There is a real ambiguity the spec does not settle, and the test settles it the
same way: **which** cycle, when a graph has several. The spec says "the path";
this review rules that a single representative cycle is sufficient, and that it
must be a genuine traversal — see the adjacent defect below.

**Ruling: the spec is right, the code is wrong.** `CpmEngine.php:103` now runs
a tri-colour depth-first search and returns the first cycle found as a closed
path (`[A,B,C,A]`).

**Corrected expectation** — unchanged from the spec: `ok === false`,
`cycle == ['A','B','C','A']`.

### 5.1 Adjacent defect, worse than the spec wording covers

The old code computed the cycle as a **set difference**:

```php
$cycle = array_values(array_diff(array_keys($byId), $order));
```

That is "everything the topological sort could not place", which contains not
only the tasks *on* a loop but every acyclic task that merely **hangs off** one.
For `A→B`, `B→A`, `A→E` it returned `['A','B','E']`: a UI would have told the
user that E, which is on no loop whatsoever, is part of their cycle. The spec's
single sentence never promised the traversal was loop-only, so the approved
corpus could not have caught it. It is now covered by
`testCycleReportExcludesAcyclicTasks` (`A→B, B→A, A→E` ⇒ `cycle` contains no
`E`), which is why that test exists in the suite outside the 20 approved cases.

---

## 6. Not in the failing five, but the corpus is wrong here too

`UT-PM-006-001-004` — "FF: predecessor EF+lag: B es = A.ef+2 = 10" (A(8), B(5)).

**Standard CPM says.** FF constrains the successor's **finish**:
`EF_B >= EF_A + lag`. Since `EF_B = ES_B + dur_B`, the earliest start that
satisfies it is `ES_B = EF_A + lag - dur_B` — here `10 - 5 = 5`, not `10`. The
engine and the approved case both write the *finish* candidate into the *start*
field, so the successor starts 5 days later than FF requires and its float is
computed from a start that FF never gated. dotProject and OpenProject both
implement FF as "predecessor finish + lag ⇒ successor **finish**"; LibreProject
likewise. The SF sibling case (`UT-PM-006-001-005`) is correct, because SF's
candidate is written as `EF_p + lag - dur_succ` — the very expression FF is
missing.

**Ruling: the spec is wrong; the code follows the spec, so no code change was
made.** Correcting the engine here would turn a currently-green approved case
red, which is an owner decision, not a silent fix. Proposed corrected
expectation: `B es = 5, ef = 10` with `B` critical in that network.

Two smaller inconsistencies, documented but not acted on:

- `UT-PM-006-001-002` says lag is "≥0 only" while `UT-PM-006-003-003` requires
  negative lag to be "recorded as-is". The engine accepts negative lag raw,
  which is the right call (MS Project supports leads). Recommend deleting the
  "(≥0 only)" parenthetical from `UT-PM-006-001-002`.
- The forward pass floors ES at the data date (`$cand = 0` seed,
  `CpmEngine.php:184`), so an SS or FF edge with a lead that resolves before
  day 0 is clamped to 0 rather than going negative. Standard CPM lets a
  predecessor's start drive a successor before the data date. No approved case
  reaches it (the one negative-lag case is FS, resolving to day 6). Recommended
  fix, not applied: initialise `es` to `null` and seed the `max()` from the
  first candidate rather than from 0.

---

## 7. Independent check: the engine now reproduces a textbook network

Not a substitute for the 20 cases — a check that the passes are right in the
absolute sense rather than self-consistent. The textbook five-task CPM network
used in the standard worked examples (durations and edges are given here so the
check is self-contained): durations 1=3, 2=2, 3=4, 4=5, 5=6 with
`1→2, 1→3, 2→4, 3→4, 4→5`; critical path `1-3-4-5`, project duration 18.

| | at `23f9b4a` (before) | after the fixes | textbook |
|---|---|---|---|
| task 1 | ls=−3, lf=0, float=−3 | ls=0, lf=3, float=0 | float 0 |
| task 2 | ls=3, lf=5, float=0 | ls=5, lf=7, float=2 | float 2 |
| task 3 | ls=−1, lf=3, float=−4 | ls=3, lf=7, float=0 | float 0 |
| task 4 | ls=2, lf=7, float=−5 | ls=7, lf=12, float=0 | float 0 |
| task 5 | ls=12, lf=18, float=0 | ls=12, lf=18, float=0 | float 0 |
| `critical` | `2, 5` | `1, 3, 4, 5` | `1, 3, 4, 5` |
| `project_duration` | 18 | 18 | 18 |

Reproduce with `CpmEngineTest.php` plus the ad-hoc snippet recorded in
`RTM-PM-006-cpm.md` §How this file was generated.

---

## 8. Consequences for the requirement documents

These rulings change approved text. Nothing was renumbered or rewritten in this
pass; the edits below are what the owner needs to apply (or delegate):

| Document | Text now | Required edit |
|---|---|---|
| `UT-PM-006-cpm-unit.md:25` | `slack=abs(ls−es); critical=slack<=0.5` | `slack=ls−es` (total float, signed); `critical = 0<=slack<=0.5` |
| `UT-PM-006-cpm-unit.md:14` (case 004) | `B es = A.ef+2 = 10` | `B es = A.ef+lag−dur_B = 5`, `ef=10` (owner ruling required — makes a green case red) |
| `FR-PM-006-001:23` | `slack=LS−ES`, `abs(slack)<=0.5` | keep `LS−ES`; change criticality to the one-sided band |
| `FR-PM-006-001:18` (case 004) | FF via predecessor EF+lag | see §6 |
| `UT-PM-006-cpm-unit.md:12` | lag "(≥0 only)" | drop, per §6 |