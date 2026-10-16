# BR-PM-010 — PM Application Tab Surface & Front Controller

**Modules:** `ksf_FA_ProjectManagement` (FA module, branch `main`)
**Companion library:** `~/Documents/ksf_ProjectManagement` (branch `v7.4`, `ksfraser/ksf-common-pm`, framework-agnostic)
**Shared dependency:** `ksfraser/ksf-fa-common` — **`AbstractAppShell`, `AbstractTabController`, `TabRegistration` shipped; `AbstractFrontController` NOT yet vendored**
**Status:** **Proposed (design only). Nothing in this BR is implemented.**
**Grounded in:** `ProjectDcs/Business Requirements.md` (RBAC), `ProjectDcs/ARCH/ARCH-PROJECT-capabilities-inventory.md`, `ProjectDcs/ARCH/ARCH-PM-vs-dotProject-vs-OpenProject-vs-OrangeHRM.md`, `../ksf_ProjectManagement/ProjectDcs/Business Requirements.md` (scope + reference comparison)
**Reference implementations:** `ksf_Architecture_Docs/HRM_Controller_Consolidation.md`, `ksf_Architecture_Docs/CRM_FrontController_Adoption.md`
**Date:** 2026-10-13

---

## Correction to a stated premise

The brief this BR answers said *"PM has not adopted the front controller — it has
no app shell yet and its tabs need creating."* **The second half is wrong**, and
recording it here prevents a future contributor from rebuilding what exists.

**Verified in the tree on 2026-10-13:**

| Claim | Reality |
|---|---|
| "PM has no app shell yet" | **False.** `src/Ksfraser/FrontAccounting/ProjectManagement/App/PmAppShell.php` exists, extends `AbstractAppShell`, uses `appId = 'pm'` → hook **`pm_register_tabs`**, base URL `index.php`, view param `view`, default tab `dashboard`. Covered by `tests/Unit/PmAppShellTest.php`, which asserts `getRegisterHook() === 'pm_register_tabs'`. |
| "its tabs need creating" | **False.** Six tabs are registered and **all six controller classes exist** under `src/Ksfraser/FrontAccounting/ProjectManagement/Controller/`: `DashboardTabController` (119 lines), `ProjectsTabController` (107), `TasksTabController` (123), `TeamTabController` (118), `ReportsTabController` (109), `ProjectTypesTabController` (122). |
| "PM has not adopted `AbstractFrontController`" | **True.** `vendor/ksfraser/ksf-fa-common/src/App/` contains `AbstractAppShell.php`, `AbstractTabController.php`, `TabRegistration.php` and **no** `AbstractFrontController.php`. `index.php` is still a 59-line hand-rolled preamble. |

**So the real gap is one thing, and it is narrow: PM has an app shell and six
tabs, and its `index.php` does not use the shared front controller.** That
single difference is worth closing, and this BR is scoped to exactly that plus
the tab-structure questions that follow from it.

---

## Business Need (BABOK What)

`ksf_Architecture_Docs/HRM_Controller_Consolidation.md` §5 records the exact
class of defect the shared base class exists to remove. When an app has several
tabs contributed by other modules, a hand-rolled preamble has to:

1. resolve the tab **before** `session.inc` (because `$page_security` must be
   set first), which means the register hook — which needs a session — has not
   fired yet;
2. fire the hook after the session;
3. re-resolve, so a contributed tab is reachable;
4. remember to re-check that contributed tab's **own** security area.

Steps 3 and 4 are each a step a hand-rolled router has to remember. HRM's
`AbstractFrontController` makes them structural and non-bypassable by making
`run()` and `enforceSecurity()` `final`.

**PM's specific exposure.** `pm_register_tabs` is already a live, documented
extension point (`AbstractAppShell::getRegisterHook()` returns it, and
`index.php:46` fires it). But `index.php` resolves `$page_security` at line 37 —
**before** `boot()` at line 46 — and then never re-checks. Any module that
contributes a tab to PM today gets its tab served under
`SA_ksf_FA_ProjectManagementVIEW`, i.e. PM's broad read area, regardless of what
area the contributor declared. That is the fail-open direction.

---

## Business Requirement

1. **PM shall adopt
   `ksfraser\FrontAccounting\Common\App\AbstractFrontController`** (package
   `ksfraser/ksf-fa-common`, ≥ **v1.0.13**; **v1.0.14** for the
   `loadUiIncludes()` fix) so that a `pm_register_tabs` contribution is checked
   against **its own** area.
2. **PM's published URL surface shall not change.** `?view=` is what
   `PmAppShell` mints (`parent::__construct('pm', 'index.php', 'view', 'dashboard')`).
   `?tab=` is accepted as the house-standard alias but never emitted — the same
   decision CRM made, for the same reason (`AbstractTabController::preserveParams()`
   defaults to `['view']` and `MasterSummaryTable::pagerUrl()` copies those
   `$_GET` keys verbatim).
3. **`normalise_get` shall be `false`** and **`load_ui_includes` shall be
   `false`**, matching CRM, because `index.php` already includes
   `includes/ui.inc` at global scope (line 43).
4. **PM shall not lose date-picker behaviour.** `index.php:49-51` already appends
   `get_js_date_picker()` when `user_use_date_picker()`; that moves into
   `getPageJs()` and stays unconditional, because narrowing it would be a
   behaviour change, not a cleanup.
5. **PM's tab set shall cover the capabilities that already have a schema**,
   without adding scope. See §"Tab structure" below.
6. **No behaviour change for PM's own users** beyond the security correction.

---

## Tab structure — grounded in what already exists

PM's six shipped tabs map to the ✅ rows of
`ARCH-PROJECT-capabilities-inventory.md`:

| Tab key | Label | Controller | Inventory row |
|---|---|---|---|
| `dashboard` | `&Dashboard` | `DashboardTabController` | (rollup) |
| `projects` | Projects | `ProjectsTabController` | "Projects CRUD + types" ✅ |
| `tasks` | Tasks | `TasksTabController` | "Tasks w/ hierarchy", "Assignments + allocation %", "Time tracking", "Progress % + activity log" ✅ |
| `team` | Team | `TeamTabController` | "Resource Management" (§In Scope 3 of the library BR) |
| `reports` | Reports | `ReportsTabController` | "visibility to project status for all stakeholders" |
| `project_types` | Project Types | `ProjectTypesTabController` | "Projects CRUD + types" ✅ |

**Gaps against the ▣ (architected, pending code) rows — schema is already in
`sql/install.sql`, only the tab/controller is missing:**

| Inventory row | Existing schema | Proposed tab | Do **not** do |
|---|---|---|---|
| Milestones (`is_milestone`, target date) — BR-PROJECT-003 | `0_fa_pm_tasks.is_milestone` (+ `idx_milestone`) | **No new tab.** It is a column on Tasks. Add an `is_milestone` filter to the Tasks tab and an `is_milestone` filter value to the CPM engine. | Do **not** create a `0_fa_pm_milestones` table — milestones are a task flag by design. |
| Task dependencies FS/SS/FF/SF + lag — FR-PROJECT-003-002 | `0_fa_pm_task_dependencies` | A **`task_dependencies` tab**, or a sub-view of Tasks. Recommend **a sub-view first** — the dependency graph is meaningful only beside the task list, and a standalone tab has nothing to select from without a project context. | Do not split it into its own CRUD tab before the Tasks tab can pass a project/date filter context. |
| CPM schedule (ES/EF/LS/LF/slack/critical) — ARCH-PROJECT-003 | `0_fa_pm_tasks` carries `es ef ls lf slack is_critical is_constrained constraint_type constraint_date` | **No new tab.** These are columns on Tasks; expose them as Tasks-tab columns with a "critical only" filter. | Do not build a seventh catalogue tab that duplicates the Tasks CRUD. |
| Documents / attachments — BR-PROJECT-004 | `0_fa_pm_files` | **A `documents` tab.** This one genuinely is a separate entity with its own CRUD. | Do not reuse the FA Documents module's tables — BR-PROJECT-004 says FA-native reuse, and the ownership boundary is FR-CRM-002-style, not a shortcut. |
| Calendar feed → FA Calendar — FR-PROJECT-005-001 | n/a (emitter) | **No tab.** This is a responder, not a screen. | Do not add a calendar tab; FA Calendar owns the screen. |
| Gantt visualization — FR-PROJECT-005-002 | n/a (render) | **No separate tab.** Render inside `projects` / `tasks`, behind a view switch that preserves the filter. | Do not create a `gantt` tab that loses the current project filter. |

**So the target tab set is seven, not six:**

```
dashboard · projects · tasks · documents · team · reports · project_types
```

plus an in-tab **Task Dependencies** sub-view. `task_dependencies` is the only
candidate that could reasonably also be a top-level tab; the recommendation above
is sub-view, and that is a design decision to confirm, not a fact.

### Security areas

All six shipped tabs currently declare the **same** area,
`SA_ksf_FA_ProjectManagementVIEW` (`PmAppShell.php:60-65`). That is a
deliberate simplification and **it is also the reason the fail-open above is
low-impact today**: there is nothing finer to fall back to.

**Requirement:** PM already declares both `SA_ksf_FA_ProjectManagementVIEW` and
`SA_ksf_FA_ProjectManagementMANAGE` in `install_access()` (`hooks.php:69-80`) but
gives **every** tab the `VIEW` one. Before a third-party module is encouraged to
contribute a PM tab, PM should route its mutation-bearing tabs through the
`MANAGE` area it already has:

```php
// already declared — no new area needed for the first split:
$security_areas['SA_ksf_FA_ProjectManagementVIEW']   = SS_ksf_FA_ProjectManagement | 1, _("View ");
$security_areas['SA_ksf_FA_ProjectManagementMANAGE'] = SS_ksf_FA_ProjectManagement | 2, _("Manage ");
```

Per-tab areas beyond that (`SA_FA_PM_TASK`, `SA_FA_PM_TEAM`,
`SA_FA_PM_REPORTS`, `SA_FA_PM_DOCUMENTS`) are a separate, later change.

> Only the **`SA_*` string is a stable contract.** `add_access_extensions()`
> reassigns every section and area code at runtime
> (`AGENTS_ARCH.md` §8). Never store, log or compare the integer.

---

## Scope boundary

**In scope:** adopt `AbstractFrontController`; add the two option overrides;
move the date-picker bundle into `getPageJs()`; add `documents` tab; add the
task-dependencies sub-view; split the security areas.

**Explicitly out of scope** — each is already owned by another BR and must not be
re-litigated here:

- CPM engine behaviour and the cycle guard (`ARCH-PROJECT-003`, `FR-PROJECT-003-002`).
- Fixed-price contracts (`BR-PROJECT-001`), templates (`BR-PROJECT-002`).
- Standalone CPM package spin-out (`BR-PM-006`).
- Cross-module event-close / membership responder (`BR-007`).
- RBAC role matrix (the module's `ProjectDcs/Business Requirements.md`) — that is
  **record-level** authorisation and is a different layer from
  `check_page_security()`. It is not solved by adopting the front controller and
  this BR does not claim to solve it.
- Time tracking, resource capacity, customer portal — In Scope items 2–4 of
  `../ksf_ProjectManagement/ProjectDcs/Business Requirements.md`, not touched here.

## Acceptance

| ID | Criterion |
|---|---|
| ARI-1 | `index.php` reduces to the two-phase bootstrap and is no longer a hand-rolled preamble. |
| ARI-2 | A module contributing a tab through `pm_register_tabs` is reachable at `index.php?view=<key>` and is refused when the user lacks **its own** area. |
| ARI-3 | `?view=` is unchanged for all six existing tabs; `?tab=` also resolves. |
| ARI-4 | Pager links read `?page=2&view=tasks` and stay on the tab. |
| ARI-5 | `?page=<n>` is never read as a tab selector. |
| ARI-6 | Every PM POST save still persists (i.e. the existing `handlePost()` path is not short-circuited by `dispatchAction()`). |
| ARI-7 | Output is byte-identical before vs after adoption across the six tabs plus the unknown-tab and bare-URL cases. |
| ARI-8 | `includes/ui.inc` remains at global scope; `load_ui_includes => false`. |
| ARI-9 | `hooks.php` is byte-identical across the adoption commit, except for the `install_access()` split. |
| ARI-10 | The date picker is present on the same tabs before and after. |
| ARI-11 | `composer.lock` moves to `ksf-fa-common` ≥ v1.0.14; deployed vendor has `dev: false`. |

## Verification planned (NOT yet performed)

Everything below is a plan. **No PM front-controller work has been written,
tested or deployed.**

- `php -l` on host PHP 8.1, container PHP 7.4.33, and the PM 7.3 lint.
- `vendor/bin/phpunit` — the existing `tests/Unit/PmAppShellTest.php` plus a new
  `tests/Unit/PmFrontControllerTest.php` modelled on
  `ksf_FA_HRM/tests/Unit/HrmFrontControllerTest.php` (32 cases) and
  `ksf_FA_CRM/tests/Unit/CrmFrontControllerTest.php` (16 cases).
- A **container CLI run** inside `ksfii_app-fa` printing `tabs=7`,
  `hook=pm_register_tabs`, and `page=tasks -> dashboard` (proving the pager is not
  a selector).
- Byte-for-byte probe comparison across the six tabs pre/post adoption.
- **One authenticated browser pass.** None of the CRM/HRM front-controller
  verification was authenticated end-to-end; do not repeat that omission. The
  specific PM risk to check by hand is the **date picker**, which the CLI run
  cannot see.

## Traceability

- FR-PM-010-001 — PM front controller adoption
- ARCH-PM-010-001 — tab surface alignment against the capability inventory
- Blocked on: publishing / depending on `ksfraser/ksf-fa-common` ≥ v1.0.14 in
  PM's `composer.json`.