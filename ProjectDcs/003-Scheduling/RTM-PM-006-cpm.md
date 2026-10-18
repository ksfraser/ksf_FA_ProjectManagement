# RTM-PM-006 — Requirements Traceability Matrix — scheduling/CPM package

| | |
|---|---|
| ID | RTM-PM-006 |
| Generated | 2026-10-17, from the PHPUnit run and the source tree at commit `23f9b4a` + the CPM fix in this commit |
| Suite state | **197 tests, 584 assertions, 7 failures** (was 197 / 553 / 20) |
| Supersedes | The previous version of this file, which marked FR-006-002 (×5) and FR-006-003 (×6) "verified" while neither service had ever been loadable. It is withdrawn; the statuses below are derived from a run, not asserted. |

## How this file was generated

Reproducible, in this order:

1. `./vendor/bin/phpunit --log-junit /tmp/opencode/junit.xml` — the run that
   every "Verified / Partially verified / Not implemented / Blocked" status in
   the Test column comes from. A row's status is that test's actual outcome,
   never an opinion.
2. A 60-line script that walks the JUnit XML, extracts each test's PASS/FAIL,
   and joins it to the `@BABOK Related:` ids in that test method's own docblock
   (source read from disk, docblock located by the method signature). That join
   is what produces the "test" column, and it is also what exposed §1 of
   `ID-SCHEME-DECISION.md`: the docblocks cite UT ids belonging to other cases.
3. `Implementing` column: `grep -n` on the class/method in
   `src/Ksfraser/FrontAccounting/ProjectManagement/` — line numbers are from the
   working tree at the commit being described, not from the spec text.
4. Statuses use four values, applied by this rule:
   - **Verified** — the test passes and it exercises the named method.
   - **Partially verified** — the test passes but only covers part of the
     requirement, or covers it against a spec that this pass ruled wrong
     (`CPM-SPEC-REVIEW.md`).
   - **Not implemented** — no test passes because the code does not exist.
   - **Blocked** — the code exists and runs, but requirement text and code
     disagree and only the owner can settle which is right.

## 1. FR-PM-006-001 — CpmEngine: precedence-graph schedule computation

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| FR-PM-006-001 / UT-PM-006-001-001 | FS chain forward pass | `CpmEngine::run` — `src/.../Engine/CpmEngine.php:179` | `CpmEngineTest::testUt006001001FsChainForwardPass` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-002 | FS + lag 3 | `CpmEngine::run` `CpmEngine.php:186` (FS branch) | `…::testUt006001002FsWithLagThree` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-003 | SS edge via predecessor ES+lag | `CpmEngine::run` `CpmEngine.php:187` (SS branch) | `…::testUt006001003StartToStartEdgeHonoursPredecessorEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-004 | FF edge via predecessor EF+lag | `CpmEngine::run` `CpmEngine.php:190` (FF branch) | `…::testUt006001004FinishToFinishEdgeUsesPredecessorEf` | **Partially verified** — green against the code, but the approved expectation writes a *finish* candidate into the successor's *start*; standard CPM gives `ES = EF_p + lag − dur_succ`. See `CPM-SPEC-REVIEW.md` §6 (owner ruling required; correcting it makes a green case red) |
| FR-PM-006-001 / UT-PM-006-001-005 | SF edge via EF+lag−duration | `CpmEngine::run` `CpmEngine.php:193` (SF branch) | `…::testUt006001005StartToFinishEdgeSubtractsSuccessorDuration` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-006 | Milestone: zero duration, EF=ES | `CpmEngine::run` `CpmEngine.php:100-105` (milestone normalisation) | `…::testUt006001006MilestoneHasZeroDurationAndEfEqualsEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-007 | `start_no_earlier_than` raises ES | `CpmEngine::run` `CpmEngine.php:205-211` | `…::testUt006001007StartNoEarlierThanAnchorRaisesEs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-008 | `finish_no_earlier_than` raises EF | `CpmEngine::run` `CpmEngine.php:212-218` | `…::testUt006001008FinishNoEarlierThanAnchorRaisesEf` | **Verified** |
| FR-PM-006-001 / UT-PM-006-001-009 | Merge task takes max of incoming candidates | `CpmEngine::run` `CpmEngine.php:200` | `…::testUt006001009MergeTaskEsIsTheMaxIncomingCandidate` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-001 | Critical chain has zero total float | `CpmEngine::run` backward pass `CpmEngine.php:236-284` | `…::testUt006002001CriticalChainHasZeroSlackOnBothTasks` | **Verified** — was red at `23f9b4a` (`CpmEngine.php:183` overwrote the FS backward bound); fixed |
| FR-PM-006-001 / UT-PM-006-002-002 | Non-critical merge task has float > 0 | `CpmEngine::run` `CpmEngine.php:301` | `…::testUt006002002NonCriticalMergeTaskHasPositiveSlack` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-003 | Total float and criticality tolerance | `CpmEngine::run` `CpmEngine.php:301-306`, `CpmEngine::CRITICAL_TOLERANCE` `CpmEngine.php:51` | `…::testUt006002003SlackIsAbsoluteAndCriticalityUsesTheTolerance` | **Partially verified** — the assertion `slack == abs(ls − es)` is vacuous for a well-formed network (float is non-negative), and `abs()` is ruled a sign-convention error in the spec. Production now computes signed total float with a one-sided criticality band. Spec text pending correction |
| FR-PM-006-001 / UT-PM-006-002-004 | Milestone criticality follows float | `CpmEngine::run` `CpmEngine.php:304` | `…::testUt006002004MilestoneCriticalityFollowsSlack` | **Verified** |
| FR-PM-006-001 / UT-PM-006-002-005 | Critical id set + project duration | `CpmEngine::run` `CpmEngine.php:296-309` | `…::testUt006002005CriticalIdSetAndProjectDuration` | **Verified** — was red at `23f9b4a` (A dropped off the critical path); fixed |
| FR-PM-006-001 / UT-PM-006-003-001 | Self-dependency rejected, not a cycle | `CpmEngine::run` `CpmEngine.php:120-128` | `…::testUt006003001SelfDependencyIsRejectedAndIsNotACycle` | **Verified** — was red at `23f9b4a` (returned `cycle: ['A','A']`); fixed |
| FR-PM-006-001 / UT-PM-006-003-002 | Cycle reported as a closed path | `CpmEngine::extractCyclePath` `CpmEngine.php:367`, `walkForCycle` `CpmEngine.php:395` | `…::testUt006003002CycleIsReportedAsAClosedPath` | **Verified** — was red at `23f9b4a`; fixed |
| FR-PM-006-001 / UT-PM-006-003-003 | Negative lag used raw | `CpmEngine::run` `CpmEngine.php:137` | `…::testUt006003003NegativeLagIsUsedRaw` | **Verified** — conflicts with `UT-PM-006-001-002`'s "(≥0 only)"; see `CPM-SPEC-REVIEW.md` §6 |
| FR-PM-006-001 / UT-PM-006-003-004 | Empty task set is valid and zero length | `CpmEngine::run` `CpmEngine.php:71` | `…::testUt006003004EmptyTaskSetIsValidAndZeroLength` | **Verified** |
| FR-PM-006-001 / UT-PM-006-003-005 | Key variants, default type FS | `CpmEngine::run` `CpmEngine.php:74-101`, `:133-136` | `…::testUt006003005AcceptsAllKeyVariantsAndDefaultsTypeToFs` | **Verified** |
| FR-PM-006-001 / UT-PM-006-003-006 | Dangling references skipped | `CpmEngine::run` `CpmEngine.php:130-132` | `…::testUt006003006DanglingDependencyReferencesAreSkipped` | **Verified** |
| BR-PM-006 §5 (cycle path is real) | Cycle report names only loop members | `CpmEngine::extractCyclePath` `CpmEngine.php:367` | `CpmEngineTest::testCycleReportExcludesAcyclicTasks` | **Verified** — was red at `23f9b4a` (set-difference reported acyclic task `E`); fixed. Case is outside the 20 approved ids — see `ID-SCHEME-DECISION.md` §3 (`UT-PM-006-006-001`) |
| FR-PM-006-001 (constraint anchors) | A calendar anchor is a schedule offset, not days-since-epoch | `CpmEngine::toDuration` `CpmEngine.php:462`, `toEpochDay` `CpmEngine.php:434` | `CpmEngineTest::testCalendarDateAnchorDoesNotExplodeIntoDaysSinceEpoch` | **Verified** — was red at `23f9b4a` (a `Y-m-d` anchor gave ES ≈ 20454); fixed. Outside the 20 approved ids (`UT-PM-006-006-002`) |
| FR-PM-006-001 (open) | Signed total float; negative float means deadline violation, not criticality | `CpmEngine::run` `CpmEngine.php:301-306` | *none* | **Partially verified** — implemented at `CpmEngine.php:301-306`, but no case drives a `start_no_later_than` / `finish_no_later_than` anchor against the backward pass, so the negative-float path is unexercised |
| FR-PM-006-001 (open) | SS/FF/SF backward-pass bounds | `CpmEngine::run` `CpmEngine.php:243-261` | *none* | **Partially verified** — implemented at `CpmEngine.php:243-261`; `UT-PM-006-001-003/004/005` exercise those networks but assert ES/EF only, so the corrected LS/LF bounds are unexercised |
| FR-PM-006-001 (open) | ES may precede the data date under a lead | `CpmEngine::run` `CpmEngine.php:184` | *none* | **Not implemented** — `$cand = 0` seed at `CpmEngine.php:184` still floors ES at day 0; recommended, not applied (`CPM-SPEC-REVIEW.md` §6) |
| FR-PM-006-001 (BR-003 §2) | Package extractable with zero FA dependencies | — | *none* | **Not implemented** — no `ksfraser/ksf-common-pm` package exists in this tree; `TaskDependencyGraph`, named by `ARCH-PM-006-standalone-cpm-package.md` and required by `TaskDependencyService::currentGraph()`, was never created |

## 2. FR-PM-006-002 — Scheduler: persistence + workflow hook

Prior version of this file marked all four items verified. The service was not
loadable until this commit: its own `namespace` and its `WorkflowHooksTrait`
import were spelled `Ksfraser\…` while the module's PSR-4 root is
`ksfraser\…` (`composer.json`), so `use WorkflowHooksTrait;` raised a PHP fatal
and `schedule()` could never be called. `hooks.php:35` loads `bootstrap.php`,
which registers the vendored prefixes, so the production path resolves — but
`composer.json` and the namespace casing still disagreed, and nothing exercised
the class. Three items now have an executable basis; three are genuine
requirement/code disagreements.

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| FR-PM-006-002 (persist) | Per-task ES/EF/LS/LF/float/critical + project duration persisted via the injected repository | `SchedulingService::persist` `src/.../Service/SchedulingService.php:169`; `TaskRepository::updateSchedule` `src/.../Repository/TaskRepository.php:93` | `SchedulingServiceTest::testSchedulePersistsComputedFieldsViaInjectedRepository` | **Verified** |
| FR-PM-006-002 (units) | `estimated_hours` → duration days (8h/day) | `SchedulingService::normalizeTasks` `SchedulingService.php:97` | `…::testScheduleConvertsEstimatedHoursToDurationDays` | **Verified** |
| FR-PM-006-002 (early exit) | `ok=false` path returns early: no hook fired, no field written | `SchedulingService::schedule` `SchedulingService.php:67-87` | `…::testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting` | **Verified** |
| FR-PM-006-002 (loadability) | Service is loadable at all | `SchedulingService` `SchedulingService.php:29-31` | `…::testSchedulePersistsComputedFieldsViaInjectedRepository` (child-process probe, exit 0) | **Verified** — was red at `23f9b4a`; fixed |
| FR-PM-006-002 (signature) | `schedule(projectsId, startDate, tasks, dependencies[, endDate])` | `SchedulingService::schedule` `SchedulingService.php:67` (`schedule(string $projectId)`) | `…::testScheduleSignatureMatchesTheFunctionalRequirement` | **Blocked** — the FR's 4/5-arg contract and the shipped 1-arg contract are both plausible (the shipped one reads tasks/edges from the injected repositories; the FR's `$startDate` is also the CPM origin the engine now needs for dated anchors). Owner ruling required |
| FR-PM-006-002 (hook count) | Fires a **single** `schedule_saved` hook | `SchedulingService::schedule` `SchedulingService.php:78-86`; registration `SchedulingService.php:50` | `…::testScheduleFiresScheduleSavedExactlyOnce` | **Blocked** — the code fires two (`pm_schedule_schedule_after`, then `pm_schedule_schedule_saved`) and prefixes with `pm_schedule`, where the FR says `pm_sched`. Two hooks vs one is an inter-module contract change |
| FR-PM-006-002 (hook payload) | Payload = `{project_id, duration, critical[], ok}` | `SchedulingService::schedule` `SchedulingService.php:78`, `:83` | `…::testScheduleHookPayloadCarriesDurationCriticalSetAndOk` | **Blocked** — the code ships `{project_id, result, persisted}`; `duration` is not surfaced at the top level. Payload shape is a published contract |
| FR-PM-006-002 (endDate) | Default null `endDate` uses project end | `SchedulingService::schedule` `SchedulingService.php:67` | *none* | **Not implemented** — no `$endDate` parameter exists to default |

## 3. FR-PM-006-003 — Dependency guard: cycle + self-reference

| Requirement | Title | Implementing | Test | Status |
|---|---|---|---|---|
| FR-PM-006-003 (self-ref) | Self dependency rejected, nothing written | `TaskDependencyService::validate` `src/.../Service/TaskDependencyService.php:118-128` | `TaskDependencyServiceTest::testSelfDependencyIsRejectedWithoutAWrite` | **Verified** |
| FR-PM-006-003 (delete) | `deleteAllFor` removes all edges of a task | `TaskDependencyRepository::deleteAllFor` `src/.../Repository/TaskDependencyRepository.php:149` | `TaskDependencyServiceTest::testDeleteAllForRemovesEveryEdgeOfATask` | **Verified** |
| FR-PM-006-003 (loadability) | Service is loadable at all | `TaskDependencyService` `TaskDependencyService.php:34-36` | `…::testServiceClassIsLoadable` | **Verified** — was red at `23f9b4a`; fixed |
| FR-PM-006-003 (method name) | `TaskDependencyService::add` is the documented entry point | `TaskDependencyService::createDependency` `TaskDependencyService.php:70` | *none* | **Blocked** — the FR names `add`, the code ships `createDependency`; the tests call `createDependency`. Renaming either side changes published API |
| FR-PM-006-003 (cycle guard) | A back-edge that closes a cycle is rejected, nothing written | `TaskDependencyService::guardCycle` `TaskDependencyService.php:141-151`; `currentGraph` `:158` | `…::testBackEdgeClosingACycleIsRejectedWithoutAWrite` | **Not implemented** — `guardCycle()` calls `CpmEngine::detectCycle()` and `TaskDependencyGraph`, neither of which exists; the guard cannot run |
| FR-PM-006-003 (cycle guard) | Guard collaborators are reachable | `CpmEngine::detectCycle`, `Engine\TaskDependencyGraph`, `TaskDependencyRepository::all` — none present | `…::testCycleGuardCollaboratorsExist`, `…::testRepositoryExposesTheRowScanTheGuardDependsOn` | **Not implemented** — three missing symbols. Creating a new public graph type is a design decision, and `BR-PM-006` §3 places graph/cycle types in the standalone package, which does not exist in this tree |
| FR-PM-006-003 (happy path) | An acyclic edge persists and returns its id | `TaskDependencyService::createDependency` `TaskDependencyService.php:70-96`; `TaskDependencyRepository::insert` `TaskDependencyRepository.php:119` | `…::testAcyclicEdgePersistsAndReturnsAnId` | **Not implemented** — `createDependency()` scans `$this->deps->all()` (`:78`) before the guard; `all()` is undefined, so the method fatals before it can write |

## 4. BR-PM-006 acceptance criteria

| Criterion | Status | Evidence |
|---|---|---|
| Package installs as `ksfraser/ksf-common-pm`, zero FA deps | **Not implemented** | no package in this tree; the logic is still in `src/` |
| `CpmEngine::run()` returns `{ok, tasks, project_duration, critical, cycle?}` for FS/SS/FF/SF with lag, milestones and anchors | **Partially verified** | 18 of the 20 approved cases pass; FF's forward candidate follows the approved (non-standard) expectation — `CPM-SPEC-REVIEW.md` §6 |
| A dependency cycle returns `ok=false` + the cycle path, never throws | **Verified** | `CpmEngineTest::testUt006003002CycleIsReportedAsAClosedPath`, `…::testCycleReportExcludesAcyclicTasks`, `…::testUt006003001…`; plus `SchedulingServiceTest::testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting` for the no-throw path at the service boundary |
| FA module declares the package and DI-injects the engine | **Verified** (injection only) | `SchedulingService::__construct` `SchedulingService.php:42-51` accepts an injected `CpmEngine`; the package dependency it implies does not exist |
| Suite green, RTM auto-generated | **Partially verified** | 190/197 pass; the 7 failures are the FR-002 and FR-003 contract items above, not engine defects |

## 5. Summary by status

46 rows, counted one per requirement/coverage line above (not per assertion, and
not per failing assertion — a row with two red tests is one row).

| Status | Rows | Which |
|---|---|---|
| **Verified** | 29 | FR-001 approved cases `001-001..003-006` minus the two flagged below (18) + both defect regressions (2); FR-002 persist / hours→days / early-exit / loadability (4); FR-003 self-ref / deleteAllFor / loadability (3); BR-006 cycle-path and no-throw (2) + DI injection (1) |
| **Partially verified** | 6 | FR-001 FF forward candidate; FR-001 `abs()` slack rule + one-sided band; FR-001 signed-float rule and SS/FF/SF backward bounds (implemented, no case exercises them); BR-006 `run()` return shape for FF; BR-006 "suite green / RTM generated" |
| **Not implemented** | 7 | FR-002 `$endDate`; FR-003 cycle guard rejected back-edge, missing guard collaborators (`CpmEngine::detectCycle`, `TaskDependencyGraph`, `TaskDependencyRepository::all`), acyclic-edge happy path; FR-001 lead-before-data-date (`$cand = 0` seed); FR-001 and BR-006 package extraction (`ksfraser/ksf-common-pm` does not exist) |
| **Blocked** | 4 | FR-002 `schedule()` signature, single-`schedule_saved` hook count, hook payload shape; FR-003 `add` vs `createDependency` |

The 7 failing tests in the suite are distributed across 6 rows: 3 FR-002
contract rows (4 tests — the hook-count and hook-payload rows) and 3 FR-003 rows
(3 tests). Every other row is either green or has no passing test behind it.

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
