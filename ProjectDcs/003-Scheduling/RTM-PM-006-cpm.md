# RTM-PM-006 — Requirements Traceability Matrix — scheduling/CPM package

| | |
|---|---|
| ID | RTM-PM-006 |
| Generated | 2026-10-17, from the PHPUnit run of the tree at this commit, after the owner rulings in `CPM-SPEC-REVIEW.md` §0.1 |
| Suite state | **200 tests, 615 assertions, 0 failures** (was 197 / 584 / 7) |
| Supersedes | The version of this file that marked FR-006-002 (×5) and FR-006-003 (×6) "verified" while neither service had ever been loadable — withdrawn; and the version immediately before this one, whose `Blocked` and `Not implemented` rows are now closed by rulings 1–6. |

## How this file was generated

Reproducible, in this order:

1. `./vendor/bin/phpunit --log-junit /tmp/opencode/junit.xml` — the run that
   every "Verified / Partially verified / Not implemented" status in the Test
   column comes from. A row's status is that test's actual outcome, never an
   opinion. If a row's named test is red, the row is not "Verified".
2. A join script (`/tmp/opencode/rtm_join.php`) that walks the JUnit XML,
   takes each test's PASS/FAIL, and joins it to the `@BABOK Related:` ids in
   that test method's own docblock (source read from disk; the docblock located
   as the nearest `/** … */` above the method signature). That join produces
   the Test column, and it is also what keeps §1 of
   `ID-SCHEME-DECISION.md` from regressing: the docblocks now cite the
   canonical UT ids, and any test whose docblock cites an id unrelated to its
   subject shows up as a mismatch between this table and the spec.
3. `Implementing` column: `grep -n` on the class/method in
   `src/Ksfraser/FrontAccounting/ProjectManagement/` — line numbers are from
   the working tree at the commit being described, not from the spec text.
4. Statuses use three values, applied by this rule:
   - **Verified** — a test passes and it exercises the named method against the
     text as it now stands in the requirement.
   - **Partially verified** — a test passes but only covers part of the
     requirement, or covers it against a requirement that is itself only
     partially exercised.
   - **Not implemented** — no test passes because the code does not exist, or
     the requirement has no executable referent.

   The fourth value the previous version used, **Blocked**, is gone: a Blocked
   row meant the code and the requirement disagreed and only the owner could
   settle it. All four such rows were ruled on (`CPM-SPEC-REVIEW.md` §0.1) and
   now read either Verified or Not implemented. Nothing in this package is
   Blocked any more.

## 1. FR-PM-006-001 — CpmEngine: precedence-graph schedule computation

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| FR-PM-006-001 / UT-PM-006-001-001 | FS chain forward pass | `CpmEngine::run` — `src/.../Engine/CpmEngine.php:78`, forward pass `:191-215` | `CpmEngineTest::testUt006001001FsChainForwardPass` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-002 | FS + lag 3 | `CpmEngine::run` `CpmEngine.php:210` (FS branch) | `…::testUt006001002FsWithLagThree` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-003 | SS edge via predecessor ES+lag | `CpmEngine::run` `CpmEngine.php:194` (SS branch) | `…::testUt006001003StartToStartEdgeHonoursPredecessorEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-004 | FF gates the successor's finish: `ES_succ = EF_pred + lag − dur_succ` | `CpmEngine::run` `CpmEngine.php:203` (FF branch) | `…::testUt006001004FinishToFinishEdgeGatesTheSuccessorFinish` | **Verified** — the case was **green-but-wrong** before ruling 6: spec and engine agreed on writing a finish candidate into a start field. Both corrected; the test now also asserts the FF network's project duration and criticality, which the wrong expectation could not have |
| FR-PM-006-001 / UT-PM-006-001-005 | SF edge via EF+lag−duration | `CpmEngine::run` `CpmEngine.php:206` (SF branch) | `…::testUt006001005StartToFinishEdgeSubtractsSuccessorDuration` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-006 | Milestone: zero duration, EF=ES | `CpmEngine::run` `CpmEngine.php:108-112` (milestone normalisation) | `…::testUt006001006MilestoneHasZeroDurationAndEfEqualsEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-007 | `start_no_earlier_than` raises ES | `CpmEngine::run` `CpmEngine.php:219-224` (start_no_earlier_than) | `…::testUt006001007StartNoEarlierThanAnchorRaisesEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-008 | `finish_no_earlier_than` raises EF | `CpmEngine::run` `CpmEngine.php:225-230` (finish_no_earlier_than) | `…::testUt006001008FinishNoEarlierThanAnchorRaisesEf` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-009 | Merge task takes max of incoming candidates | `CpmEngine::run` `CpmEngine.php:213` | `…::testUt006001009MergeTaskEsIsTheMaxIncomingCandidate` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-001 | Critical chain has zero total float | `CpmEngine::run` backward pass `CpmEngine.php:248-297` | `…::testUt006002001CriticalChainHasZeroSlackOnBothTasks` | **Verified** — was red at `23f9b4a` (the FS backward bound was overwritten with `succ.ES − pred.duration`); fixed |
| FR-PM-006-001 / UT-PM-006-002-002 | Non-critical merge task has float > 0 | `CpmEngine::run` `CpmEngine.php:314` | `…::testUt006002002NonCriticalMergeTaskHasPositiveSlack` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-003 | Total float is signed `LS−ES`; criticality is the one-sided band `0<=slack<=0.5` | `CpmEngine::run` `CpmEngine.php:314-318`, `CpmEngine::CRITICAL_TOLERANCE` `CpmEngine.php:58` | `…::testUt006002003SlackIsSignedTotalFloatAndCriticalityIsAOneSidedBand` | **Verified** — was **Partially verified**: the assertion was `slack == abs(ls − es)` and `critical == slack<=0.5`, i.e. the ruled-wrong expressions (`CPM-SPEC-REVIEW.md` §2). The test now asserts the signed rule and additionally asserts float is non-negative here, so it is no longer a vacuous equality |
| FR-PM-006-001 / UT-PM-006-002-004 | Milestone criticality follows float | `CpmEngine::run` `CpmEngine.php:317` | `…::testUt006002004MilestoneCriticalityFollowsSlack` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-005 | Critical id set + project duration | `CpmEngine::run` `CpmEngine.php:309-321` | `…::testUt006002005CriticalIdSetAndProjectDuration` | **Verified** — was red at `23f9b4a` (A dropped off the critical path); fixed |
| FR-PM-006-001 / UT-PM-006-003-001 | Self-dependency rejected, and it is not a cycle | `CpmEngine::run` `CpmEngine.php:126-135` | `…::testUt006003001SelfDependencyIsRejectedAndIsNotACycle` | **Verified** — was red at `23f9b4a` (returned `cycle: ['A','A']`); fixed |
| FR-PM-006-001 / UT-PM-006-003-002 | Cycle reported as a closed path | `CpmEngine::extractCyclePath` `CpmEngine.php:415`, `walkForCycle` `CpmEngine.php:443` | `…::testUt006003002CycleIsReportedAsAClosedPath` | **Verified** — was red at `23f9b4a`; fixed |
| FR-PM-006-001 / UT-PM-006-003-003 | Negative lag used raw | `CpmEngine::run` `CpmEngine.php:144` | `…::testUt006003003NegativeLagIsUsedRaw` | **Verified** — the `UT-PM-006-001-002` "(≥0 only)" parenthetical that contradicted this has been dropped; `001-003` is now marked as the governing statement for lag sign |
| FR-PM-006-001 / UT-PM-006-003-004 | Empty task set is valid and zero length | `CpmEngine::run` `CpmEngine.php:78` | `…::testUt006003004EmptyTaskSetIsValidAndZeroLength` | **Verified** |
| FR-PM-006-001 / UT-PM-006-003-005 | Key variants, default type FS | `CpmEngine::run` `CpmEngine.php:81-108`, `:140-143` | `…::testUt006003005AcceptsAllKeyVariantsAndDefaultsTypeToFs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-003-006 | Dangling references skipped | `CpmEngine::run` `CpmEngine.php:137-139` | `…::testUt006003006DanglingDependencyReferencesAreSkipped` | **Verified** |
| BR-PM-006 §5 (cycle path is real) | Cycle report names only loop members | `CpmEngine::extractCyclePath` `CpmEngine.php:415` | `CpmEngineTest::testCycleReportExcludesAcyclicTasks` | **Verified** — was red at `23f9b4a` (the set difference reported the acyclic task `E`); fixed. Outside the original 20 cases — `UT-PM-006-006-001` |
| FR-PM-006-001 (constraint anchors) | A calendar anchor is a schedule offset, not days-since-epoch | `CpmEngine::toDuration` `CpmEngine.php:510`, `toEpochDay` `CpmEngine.php:482` | `CpmEngineTest::testCalendarDateAnchorDoesNotExplodeIntoDaysSinceEpoch` | **Verified** — was red at `23f9b4a` (a `Y-m-d` anchor gave ES ≈ 20454); fixed. `UT-PM-006-006-002` |
| FR-PM-006-001 (backward pass) | FF's backward bound `LF_i ≤ LF_j − lag` | `CpmEngine::run` `CpmEngine.php:259-261` | *none* | **Partially verified** — implemented at `:259-261`, and `UT-PM-006-001-004` runs an FF network through the full engine including the backward pass, but it asserts ES/EF/criticality rather than the LF/LS values the bound produces |
| FR-PM-006-001 (backward pass) | SS/FF/SF backward-pass bounds | `CpmEngine::run` `CpmEngine.php:256-266` | *none* | **Partially verified** — implemented; the SS/FF/SF cases exercise those networks but assert ES/EF only, so the LS/LF bounds themselves are unexercised |
| FR-PM-006-001 (open) | Signed total float: negative float means a deadline violation, not criticality | `CpmEngine::run` `CpmEngine.php:314-318` | *none* | **Partially verified** — implemented, but no case drives a `start_no_later_than` / `finish_no_later_than` anchor against the backward pass, so the negative-float path is unexercised |
| FR-PM-006-001 (open) | ES may precede the data date under a lead | `CpmEngine::run` `CpmEngine.php:191` (`$cand = 0` seed) | *none* | **Not implemented** — ES is still floored at day 0; recommended in `CPM-SPEC-REVIEW.md` §6, not applied (outside the owner's rulings) |
| FR-PM-006-001 (BR-003 §2) | Package extractable with zero FA dependencies | — | *none* | **Not implemented** — no `ksfraser/ksf-common-pm` package exists in this tree; the logic is still in `src/`. (The `TaskDependencyGraph` that `ARCH-PM-006-standalone-cpm-package.md` names as a package type now exists under `src/…/Engine/`, but it has not been extracted.) |

## 2. FR-PM-006-002 — Scheduler: persistence + workflow hook

The three previously *Blocked* rows are closed by rulings 1–3 in
`CPM-SPEC-REVIEW.md` §0.1. In each case the ruling was that the **code** was
right and the **FR** was wrong, so the requirement text was corrected to match
the shipped contract and the FR is now the authority rather than the thing being
argued with.

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| UT-PM-006-004-001 | Per-task ES/EF/LS/LF/float/critical + project duration persisted via the injected repository | `SchedulingService::persist` `src/.../Service/SchedulingService.php:204`; `TaskRepository::updateSchedule` `src/.../Repository/TaskRepository.php:93` | `SchedulingServiceTest::testSchedulePersistsComputedFieldsViaInjectedRepository` | **Verified** |
| UT-PM-006-004-005 | `estimated_hours` → duration days (8h/day) | `SchedulingService::normalizeTasks` `SchedulingService.php:132` | `…::testScheduleConvertsEstimatedHoursToDurationDays` | **Verified** |
| UT-PM-006-004-003 | `ok=false` path returns early: no hook fired, no field written | `SchedulingService::schedule` `SchedulingService.php:91-103` | `…::testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting` | **Verified** |
| UT-PM-006-004-004 | `schedule(string $projectId)` is the whole signature; no caller-injected task set | `SchedulingService::schedule` `SchedulingService.php:91` | `…::testScheduleSignatureMatchesTheFunctionalRequirement` | **Verified** — was red against the FR's 5-parameter draft. Ruling 1: the one-argument form is the requirement, because a caller-passed `$tasks` array can be stale relative to the persisted graph |
| UT-PM-006-004-002 | `pm_schedule_schedule_after` then `pm_schedule_schedule_saved`, each exactly once | `SchedulingService::schedule` `SchedulingService.php:116`, `:121`; registration `SchedulingService.php:50` | `…::testScheduleFiresEachScheduleHookExactlyOnce` | **Verified** — was red against the FR's "a single `schedule_saved`". Ruling 2: both hooks stay; separating computed from persisted is what lets a caller render a preview without writing. The test now asserts the exact ordered pair *and* that each fires once, which is stricter than the single `assertCount(1, …)` it replaces |
| UT-PM-006-004-006 | Hook payload carries `project_id`, `ok`, `duration`, `critical[]`, the retained `result`, and `persisted` on the saved hook only | `SchedulingService::schedule` `SchedulingService.php:109-121` | `…::testScheduleHookPayloadCarriesDurationCriticalSetAndOk` | **Verified** — was red against the shipped `{project_id, result, persisted}`. Ruling 3: `duration` + `critical[]` were **added**, not substituted. The test now captures **both** hooks and asserts `persisted` is absent from the computed-only payload, which is what makes the two hooks distinguishable |
| FR-PM-006-002 (loadability) | Service is loadable at all | `SchedulingService` `SchedulingService.php:29-31` | every case in this section (child-process probe, exit 0) | **Verified** — was red at `23f9b4a`; fixed |
| FR-PM-006-002 (endDate) | *(withdrawn)* | — | *none* | **Not implemented, and no longer a requirement** — ruling 1 withdrew the "default null `endDate` uses project end" acceptance item: there is no `$endDate` parameter, so there is nothing to default |

## 3. FR-PM-006-003 — Dependency guard: cycle + self-reference

The method-name row and the three missing-symbol rows are closed by rulings 4
and 5. The guard is the feature that stops cycles reaching real data, so it is
covered from both sides: an acyclic graph is accepted, a real cycle is
detected, a self edge is refused by validation, and the refusal names the closed
path.

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| UT-PM-006-005-001 | Self dependency rejected by validation, before any read or write | `TaskDependencyService::validate` `src/.../Service/TaskDependencyService.php:128-141` | `TaskDependencyServiceTest::testSelfDependencyIsRejectedWithoutAWrite` | **Verified** |
| UT-PM-006-005-002 | A back-edge that closes a cycle is rejected and nothing is written | `TaskDependencyService::guardCycle` `TaskDependencyService.php:160-171` | `…::testBackEdgeClosingACycleIsRejectedWithoutAWrite` | **Verified** — was red: `guardCycle()` called `CpmEngine::detectCycle()`, which did not exist, and `add()` fataled on the undefined `all()` before it could even reach the guard. The assertion is now on writes rather than on "no SQL ran", because the guard must read the persisted graph to know it would close a loop |
| UT-PM-006-005-003 | An acyclic edge in an acyclic graph persists and returns its id | `TaskDependencyService::add` `TaskDependencyService.php:76-102`; `TaskDependencyRepository::insert` `TaskDependencyRepository.php:145` | `…::testAcyclicEdgePersistsAndReturnsAnId` | **Verified** — was red for the same `all()` reason |
| UT-PM-006-005-004 | `deleteAllFor` removes all edges of a task | `TaskDependencyRepository::deleteAllFor` `TaskDependencyRepository.php:175` | `…::testDeleteAllForRemovesEveryEdgeOfATask` | **Verified** |
| UT-PM-006-005-005 | `CpmEngine::detectCycle()` + `Engine\TaskDependencyGraph` exist and are reachable from the guard | `CpmEngine::detectCycle` `Engine/CpmEngine.php:357`; `Engine/TaskDependencyGraph.php:64`; wired at `TaskDependencyService.php:160` | `…::testCycleGuardCollaboratorsExist` | **Verified** — was red: all three symbols were absent |
| UT-PM-006-005-006 | `TaskDependencyRepository::all()` is the row scan the guard reads | `TaskDependencyRepository::all` `TaskDependencyRepository.php:115` | `…::testRepositoryExposesTheRowScanTheGuardDependsOn` | **Verified** — was red: `all()` did not exist |
| UT-PM-006-005-007 | `TaskDependencyService` is loadable at all | `TaskDependencyService` `TaskDependencyService.php:35-37` | `…::testServiceClassIsLoadable` | **Verified** — was red at `23f9b4a`; fixed |
| UT-PM-006-005-008 | The cycle refusal names the cycle as a closed traversal | `TaskDependencyService::guardCycle` `TaskDependencyService.php:166-170` | `…::testCycleRefusalReportsTheClosedPathBackToItsStart` | **Verified** — new case; the previous message was `"Dependency would create a cycle: <task_id> -> <predecessor_id>"`, which named only the new edge and told the user nothing about where the loop closes |
| UT-PM-006-006-003 | `detectCycle()` returns a genuine closed cycle naming only the loop's own nodes | `CpmEngine::detectCycle` `CpmEngine.php:357-365` → `extractCyclePath` `:415` | `CpmEngineTest::testDetectCycleReturnsAClosedPathOnTheRealCycleOnly` | **Verified** — new case; guards the two defects of §5/§5.1 of `CPM-SPEC-REVIEW.md` at the guard's own entry point |
| UT-PM-006-006-004 | `detectCycle()` accepts an acyclic graph | `CpmEngine::detectCycle` `CpmEngine.php:357-365` | `CpmEngineTest::testDetectCycleAcceptsAnAcyclicDiamond` | **Verified** — new case; without it a guard that always returned a path would still let every test above pass |
| FR-PM-006-003 (method name) | `TaskDependencyService::add` is the documented entry point | `TaskDependencyService::add` `TaskDependencyService.php:76` | the seven service cases above (all call `add()`) | **Verified** — was red / *Blocked*: the code shipped `createDependency` while the FR named `add`. Ruling 4: the FR was right, so the method was renamed; every call site, test and `@BABOK` reference now says `add` |

## 4. BR-PM-006 acceptance criteria

| Criterion | Status | Evidence |
|---|---|---|
| Package installs as `ksfraser/ksf-common-pm`, zero FA deps | **Not implemented** | no package in this tree; the logic is still in `src/` |
| `CpmEngine::run()` returns `{ok, tasks, project_duration, critical, cycle?}` for FS/SS/FF/SF with lag, milestones and anchors | **Verified** | all 20 approved cases of `UT-PM-006-001..003` pass, plus `UT-PM-006-006-001/002`. FF is now the textbook rule rather than the drafted one (`CPM-SPEC-REVIEW.md` §6) |
| A dependency cycle returns `ok=false` + the cycle path, never throws | **Verified** | `CpmEngineTest::testUt006003002CycleIsReportedAsAClosedPath`, `…::testCycleReportExcludesAcyclicTasks`, `…::testUt006003001…`; `SchedulingServiceTest::testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting` for the no-throw path at the service boundary |
| A cycle is never persisted | **Verified** | `TaskDependencyServiceTest::testBackEdgeClosingACycleIsRejectedWithoutAWrite` (refused) and `…::testCycleRefusalReportsTheClosedPathBackToItsStart` (refused, path reported) |
| FA module declares the package and DI-injects the engine | **Partially verified** | `SchedulingService::__construct` `SchedulingService.php:42-51` and `TaskDependencyService::__construct` `TaskDependencyService.php:50-59` accept injected repositories and an injected `CpmEngine`; the package dependency they imply does not exist |
| Suite green, RTM auto-generated | **Verified** | 200/200 pass, 0 failures; every status in this file is that run's outcome joined to the source's `@BABOK` docblocks |

## 5. Summary by status

46 rows, counted one per requirement/coverage line above (not per assertion, and
not per failing assertion — a row with two red tests is one row).

| Status | Rows | Which |
|---|---|---|
| **Verified** | 41 | FR-001 approved cases `001-001..003-006` (20) + `006-001/002` (2) + `004-001/002/003/004/005/006` (6) + FR-001 loadability (1); FR-003 `005-001..008` (8) + `006-003/004` (2); BR-006 cycle-path, no-throw, never-persisted (3) + suite green (1) |
| **Partially verified** | 3 | FR-001 FF backward bound; FR-001 SS/FF/SF backward-pass bounds; FR-001 signed-float rule on a "no later than" anchor (all implemented, none exercised end-to-end) |
| **Not implemented** | 2 | FR-001 ES-before-data-date under a lead (`$cand = 0` seed); FR-001 / BR-006 package extraction (`ksfraser/ksf-common-pm` does not exist). Plus one withdrawn item (FR-002 `$endDate`) which is counted in neither column because it is no longer a requirement |
| **Blocked** | 0 | none — all four Blocked rows of the previous version were ruled on and are now Verified (`CPM-SPEC-REVIEW.md` §0.1) |

The 7 failures of the previous run are distributed across 6 rows there (3
FR-002 contract rows covering 4 tests, 3 FR-003 rows). Every one of those rows
is now Verified, and 3 cases were **added** while closing them
(`UT-PM-006-005-008`, `UT-PM-006-006-003`, `UT-PM-006-006-004`) — the count
went 197 → 200 tests, 584 → 615 assertions, 7 → 0 failures. No test was
weakened or deleted to get there: the three FR-002 tests were rewritten to
assert the now-documented contract (one hook per phase, the one-argument
signature, the extended payload), and the two cycle cases the review had flagged
as vacuous were rewritten to assert the corrected rules instead of the ruled-wrong
`abs()` equality.

## 6. What the previous RTM got wrong

The withdrawn version was a 3-row grid of check marks: FR-006-001 all-green,
FR-006-002 "verified x5", FR-006-003 "verified x6". At the time it was written
neither `SchedulingService` nor `TaskDependencyService` could be loaded at all.
The suite did not show that as 11 fatal errors, because
`ChildPhpRunner` probes such classes out-of-process and asserts on the child's
exit code — so a class that fatals on load shows up as an ordinary red
assertion ("expected exit 0, got 255"). 9 of the 13 non-CPM failures at
`23f9b4a` were exactly that shape, and the grid had no way to express any of
them.

The lesson that shaped this version: **a traceability matrix that records only
"is there a test" cannot record "the test is red"**. Every row here carries a
status *and* a one-line reason, and the status is the test's real outcome from
the run in §How this file was generated — not an assertion about coverage.

The lesson that shaped *this* revision is the mirror image: a matrix that
records only "is there a test" also cannot record **"the test is green and both
it and the implementation are wrong"**. `UT-PM-006-001-004` was green for its
entire life while encoding an FF rule no CPM product implements. Two rows here
carry that history explicitly (`FR-PM-006-001 / UT-PM-006-001-004` and
`…/UT-PM-006-002-003`), because a reader who only sees "Verified" would
otherwise have no way to know that a green case had already been found wrong
once.