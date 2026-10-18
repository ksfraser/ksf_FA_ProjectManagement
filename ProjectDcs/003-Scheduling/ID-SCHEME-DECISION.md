# ID-SCHEME-DECISION — which PM-006 requirement id scheme wins

| | |
|---|---|
| ID | ID-SCHEME-DECISION |
| Date | 2026-10-17 |
| Question | `FR-PM-006-001/002/003` and `UT-PM-006-cpm-unit.md` both mint `*-001-00x` / `*-003-00x` ids for **different subjects**. Which scheme is canonical? |
| Status | Decision proposed, **no files renumbered**. The renumbering is an owner-approved change to Approved documents and is listed as §5. |

## 1. The collision, stated exactly

`FR-PM-006-001` §Acceptance is headed "traceable to UT" and then lists eight
ids in the UT namespace with **UT subjects that do not match** the case ids in
`UT-PM-006-cpm-unit.md`:

| id | FR-PM-006-001 says | UT-PM-006 says |
|---|---|---|
| `…-001-001` | forward pass ES/EF for FS(+lag) chain | FS chain A(8)→B(5) — **agrees** |
| `…-001-002` | SS edge honors predecessor ES+lag | **FS + lag 3** |
| `…-001-003` | FF edge via predecessor EF+lag | **SS edge, lag 2** |
| `…-001-004` | SF edge via predecessor EF+lag−duration | **FF edge, lag 2** |
| `…-001-005` | milestone EF=ES | **SF edge** |
| `…-001-006` | cycle → ok=false + cycle path | **milestone zero duration** |
| `…-001-007` | backward LF/LS; slack=LS−ES | **start_no_earlier_than anchor** |
| `…-001-008` | criticality within tolerance | **finish_no_earlier_than anchor** |
| `…-001-009` | *(absent)* | multiple-input merge |

So the two documents agree on `-001-001` and disagree on **seven** ids. Worse,
they disagree in a way that silently *reassigns* meanings: an RTM row that joins
`FR-006-001 × UT-006-001-004` claims SF coverage from a test that exercises FF.

`FR-PM-006-002` and `FR-PM-006-003` then reuse the **same id space again** for
subjects that belong to neither document:

| id | FR-PM-006-002 (§"persistence + hook") | FR-PM-006-003 (§"cycle guard") | UT-PM-006 says |
|---|---|---|---|
| `…-002-001` | persists via injected repository | — | critical chain has zero slack |
| `…-002-002` | fires `schedule_saved` exactly once | — | non-critical merge task float > 0 |
| `…-002-003` | ok=false path returns early, no hook | — | slack/criticality tolerance |
| `…-002-004` | null endDate uses project end | — | milestone criticality |
| `…-003-001` | — | self-dependency rejected | self-dependency rejected (agrees) |
| `…-003-002` | — | back-edge closing a cycle | **cycle path [A,B,C,(A)]** |
| `…-003-003` | — | acyclic edge persists + returns id | **negative lag used raw** |
| `…-003-004` | — | deleteAllFor removes all deps | **empty task set** |

Four of `FR-003`'s five ids and two of `FR-002`'s four land on unrelated CPM
cases. The executable suite inherits the damage: `SchedulingServiceTest` and
`TaskDependencyServiceTest` carry `@BABOK Related: UT-PM-006-002-001` /
`UT-PM-006-003-003` docblocks for tests that exercise *persistence* and *an
acyclic edge persisting*, citing cases that are about slack and negative lag.
Those are false traceability statements in shipped code comments.

## 2. Decision

**The UT scheme wins. The FR documents must not mint UT ids.**

Rationale:

1. A requirement's acceptance list *cites* test ids; it does not *define* them.
   Two namespaces issuing the same token with different meanings is unresolvable
   by any reader and by any RTM join.
2. The UT ids are the only ones with an executable referent: 20 approved cases,
   20 test methods, each method name carrying the id
   (`testUt006001001FsChainForwardPass`). The FR-only ids have no artefact at
   all — nothing is named after them.
3. `UT-PM-006-003-001` ("self-dependency rejected") is the single id where both
   documents agree, which is what confirms the two lists are not the same list
   rather than one being a superset.
4. The FR ids themselves (`FR-PM-006-001/002/003`) are **fine** and stay. Three
   distinct requirements, no collision, and the requirement numbering is the
   outer scheme the UT block hangs off. Nothing needs renumbering at FR level.

The FR acceptance lists must therefore be rewritten to **reference** UT ids.
That is a documentation-only change: the requirements' substance stays, only the
pointer changes.

## 3. The renumbered mapping (proposed; not applied)

The service-level acceptance items currently squatting on `…-002-00x` /
`…-003-00x` are real cases and deserve real ids. They extend the UT block with
two new groups rather than renumbering existing ones, so every approved id keeps
its meaning and its test:

| Proposed id | Current (wrong) citation | Test that exercises it |
|---|---|---|
| `UT-PM-006-004-001` | FR-002-001 / UT-002-001 | `SchedulingServiceTest::testSchedulePersistsComputedFieldsViaInjectedRepository` |
| `UT-PM-006-004-002` | FR-002-002 / UT-002-002 | `SchedulingServiceTest::testScheduleFiresScheduleSavedExactlyOnce` |
| `UT-PM-006-004-003` | FR-002-003 / UT-002-003 | `SchedulingServiceTest::testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting` |
| `UT-PM-006-004-004` | FR-002-004 / UT-002-004 | `SchedulingServiceTest::testScheduleSignatureMatchesTheFunctionalRequirement` |
| `UT-PM-006-004-005` | FR-002 (hours→days) | `SchedulingServiceTest::testScheduleConvertsEstimatedHoursToDurationDays` |
| `UT-PM-006-004-006` | FR-002 (hook payload) | `SchedulingServiceTest::testScheduleHookPayloadCarriesDurationCriticalSetAndOk` |
| `UT-PM-006-005-001` | FR-003-001 / UT-003-001 | `TaskDependencyServiceTest::testSelfDependencyIsRejectedWithoutAWrite` |
| `UT-PM-006-005-002` | FR-003-002 / UT-003-002 | `TaskDependencyServiceTest::testBackEdgeClosingACycleIsRejectedWithoutAWrite` |
| `UT-PM-006-005-003` | FR-003-003 / UT-003-003 | `TaskDependencyServiceTest::testAcyclicEdgePersistsAndReturnsAnId` |
| `UT-PM-006-005-004` | FR-003-004 / UT-003-004 | `TaskDependencyServiceTest::testDeleteAllForRemovesEveryEdgeOfATask` |
| `UT-PM-006-005-005` | FR-003 (guard collaborators) | `TaskDependencyServiceTest::testCycleGuardCollaboratorsExist` |
| `UT-PM-006-005-006` | FR-003 (row scan) | `TaskDependencyServiceTest::testRepositoryExposesTheRowScanTheGuardDependsOn` |
| `UT-PM-006-005-007` | FR-003 (loadability) | `TaskDependencyServiceTest::testServiceClassIsLoadable` |
| `UT-PM-006-006-001` | *(no id at all)* | `CpmEngineTest::testCycleReportExcludesAcyclicTasks` |
| `UT-PM-006-006-002` | *(no id at all)* | `CpmEngineTest::testCalendarDateAnchorDoesNotExplodeIntoDaysSinceEpoch` |

Groups after renumbering:

- `UT-PM-006-001-001..009` — CpmEngine forward pass (unchanged)
- `UT-PM-006-002-001..005` — CpmEngine backward pass / float / critical (unchanged)
- `UT-PM-006-003-001..006` — CpmEngine robustness (unchanged)
- `UT-PM-006-004-001..006` — **new** SchedulingService orchestration
- `UT-PM-006-005-001..007` — **new** TaskDependencyService guards
- `UT-PM-006-006-001..002` — **new** CpmEngine defect regressions (these two
  exist in the suite today with no UT id; they were recorded as "defects found
  beyond the 20 cases", which is why they had no slot)

## 4. FR acceptance lists after the decision

| FR | acceptance item | cites (after) |
|---|---|---|
| FR-PM-006-001 | forward pass ES/EF for FS(+lag) chain | UT-PM-006-001-001 |
| FR-PM-006-001 | FS + lag | UT-PM-006-001-002 |
| FR-PM-006-001 | SS edge via predecessor ES+lag | UT-PM-006-001-003 |
| FR-PM-006-001 | FF edge via predecessor EF+lag | UT-PM-006-001-004 *(see `CPM-SPEC-REVIEW.md` §6 — the case itself is wrong)* |
| FR-PM-006-001 | SF edge via predecessor EF+lag−duration | UT-PM-006-001-005 |
| FR-PM-006-001 | milestone EF=ES | UT-PM-006-001-006 |
| FR-PM-006-001 | constraint anchors raise ES/EF | UT-PM-006-001-007, UT-PM-006-001-008 |
| FR-PM-006-001 | multiple-input merge takes the max | UT-PM-006-001-009 |
| FR-PM-006-001 | backward pass LF/LS; slack = LS−ES | UT-PM-006-002-001..003 |
| FR-PM-006-001 | criticality within tolerance | UT-PM-006-002-003..005, UT-PM-006-004-004 |
| FR-PM-006-001 | cycle → ok=false + cycle path, no throw | UT-PM-006-003-001, UT-PM-006-003-002, UT-PM-006-006-001 |
| FR-PM-006-002 | persists via injected repository, no FA calls | UT-PM-006-004-001 |
| FR-PM-006-002 | fires `schedule_saved` exactly once | UT-PM-006-004-002 |
| FR-PM-006-002 | ok=false path returns early, no hook | UT-PM-006-004-003 |
| FR-PM-006-002 | schedule() signature / null endDate | UT-PM-006-004-004 |
| FR-PM-006-002 | estimated hours → duration days | UT-PM-006-004-005 |
| FR-PM-006-002 | hook payload carries duration/critical/ok | UT-PM-006-004-006 |
| FR-PM-006-003 | self-dependency rejected (no write) | UT-PM-006-005-001 |
| FR-PM-006-003 | back-edge closing a cycle rejected (no write) | UT-PM-006-005-002 |
| FR-PM-006-003 | acyclic edge persists + returns id | UT-PM-006-005-003 |
| FR-PM-006-003 | `deleteAllFor` removes all deps | UT-PM-006-005-004 |
| FR-PM-006-003 | guard collaborators exist and are reachable | UT-PM-006-005-005..007 |

## 5. What applying this decision requires (not done here)

1. Edit the three `FR-PM-006-00x` acceptance lists to cite the ids in §4.
   Text-only; no requirement changes meaning.
2. Add `UT-PM-006-004/005/006` blocks to `UT-PM-006-cpm-unit.md` for the
   service-level cases, which the UT document does not currently describe at
   all — the 20 approved cases are engine-only.
3. Correct the `@BABOK Related:` docblocks in `SchedulingServiceTest.php` and
   `TaskDependencyServiceTest.php`, which currently cite UT ids belonging to
   unrelated cases. Deliberately **not** done in this pass: those files are the
   evidence base for `RTM-PM-006-cpm.md`, and the renumbering is an
   owner-approved change to Approved documents.
4. Two FR name mismatches stay open and are *not* ids, so no scheme resolves
   them: `FR-PM-006-003` names `TaskDependencyService::add` where the code has
   `createDependency` (`TaskDependencyService.php:70`), and `FR-PM-006-002`
   documents a 5-parameter `schedule()` where the code has 1. Both are recorded
   in `RTM-PM-006-cpm.md` as Blocked.