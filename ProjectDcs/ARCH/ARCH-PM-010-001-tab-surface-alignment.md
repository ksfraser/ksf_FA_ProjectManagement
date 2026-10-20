# ARCH-PM-010-001 — PM tab surface alignment against the capability inventory

**Status:** Proposed (design only — nothing built)
**Feeds:** `BR/BR-PM-010-front-controller-and-tab-surface.md`,
`FR/FR-PM-010-001-app-shell-front-controller.md`
**Sources of truth for scope:**
- `ProjectDcs/ARCH/ARCH-PROJECT-capabilities-inventory.md` (dotProject / SuiteCRM / Odoo matrix)
- `ProjectDcs/ARCH/ARCH-PM-vs-dotProject-vs-OpenProject-vs-OrangeHRM.md`
- `~/Documents/ksf_ProjectManagement/ProjectDcs/Business Requirements.md` (scope, stakeholders, integration table)
- `sql/install.sql` (what schema actually exists)
- `src/Ksfraser/FrontAccounting/ProjectManagement/App/PmAppShell.php` (what is registered)

**Date:** 2026-10-13

---

## Question

The tab surface is PM's primary user-visible contract. Six tabs are registered.
Which of the capabilities the inventory marks `▣` (architected, pending code)
belong on the tab surface, and which are **not** screens at all?

## Method

Cross-referenced three things and deliberately preferred the **code** over the
documents where they disagree:

1. `PmAppShell::registerCoreTabs()` — what is registered today.
2. `sql/install.sql` — what tables and columns actually exist.
3. The inventory matrix — what is architected but not built.

Where an inventory row says "capability" and the schema says "column", the
capability is a **column on an existing tab**, not a new tab. That distinction is
the main output of this document.

## Finding 1 — the shipped tab set, mapped

| Tab key | Controller | Schema it reads | Inventory row |
|---|---|---|---|
| `dashboard` | `DashboardTabController` | `0_ksf_projectmanagement_projects`, `0_ksf_projectmanagement_tasks`, `0_ksf_projectmanagement_project_revenue` | (rollup; library BR: "provide visibility to project status for all stakeholders") |
| `projects` | `ProjectsTabController` | `0_ksf_projectmanagement_projects`, `0_ksf_projectmanagement_project_types`, `0_ksf_projectmanagement_project_sales_orders`, `0_ksf_projectmanagement_project_revenue` | Projects CRUD ✅, Customer/debtor integration ✅, Revenue/orders tracking ✅ |
| `tasks` | `TasksTabController` | `0_ksf_projectmanagement_tasks`, `0_ksf_projectmanagement_assignments`, `0_ksf_projectmanagement_task_progress`, `0_ksf_projectmanagement_activity_log` | Tasks w/ hierarchy ✅, Assignments + allocation ✅, Time tracking ✅, Progress % + activity log ✅ |
| `team` | `TeamTabController` | `0_ksf_projectmanagement_assignments` (+ HRM) | Library BR In Scope 3: Resource Management |
| `reports` | `ReportsTabController` | aggregates across the above | Library BR: stakeholder visibility |
| `project_types` | `ProjectTypesTabController` | `0_ksf_projectmanagement_project_types` | Projects CRUD + types ✅ |

**Conclusion:** every `✅` row in the inventory has a tab. The shipped surface is
internally consistent.

## Finding 2 — the `▣` rows, classified by whether they are screens

| Inventory row | Schema status | Classification | Rationale |
|---|---|---|---|
| **Milestones** (`is_milestone`, target date) | `0_ksf_projectmanagement_tasks.is_milestone TINYINT(1)` + `idx_milestone` | **Column on `tasks`** | A milestone in this design *is* a task with a flag and a date. There is no milestone entity, and none should be created. |
| **CPM schedule** (ES/EF/LS/LF/slack/critical) | `0_ksf_projectmanagement_tasks` carries `es ef ls lf slack is_critical is_constrained constraint_type constraint_date` | **Columns on `tasks`** | Same argument. A separate CPM tab would duplicate the Tasks CRUD and force the user to correlate two screens by hand. |
| **Scheduling constraints** (SNET/SNLT/Must Start…) | `is_constrained`, `constraint_type`, `constraint_date` on `0_ksf_projectmanagement_tasks` | **Columns on `tasks`** | Same. |
| **Task dependencies** (FS/SS/FF/SF + lag) | `0_ksf_projectmanagement_task_dependencies` **exists** | **Sub-view of `tasks`** — design decision to confirm | It *is* a separate entity with its own rows. But it is only meaningful beside the task list: the editor needs a task selected to add a successor/predecessor. A standalone tab would have to re-implement project context. Recommend sub-view first. |
| **Cycle guard** (no DAG loops) | n/a — `FR-PROJECT-003-002` | **Not a screen** | A validation on write. The inventory's own finding #4 calls this the differentiating capability, and it is a rule, not a tab. |
| **Documents / attachments** | `0_ksf_projectmanagement_files` **exists** | **A new `documents` tab** | Separate entity, separate CRUD, separate lifecycle (upload, diff-save location, retention). This is the one genuine tab addition. |
| **Calendar feed → FA Calendar** | n/a — emitter (`FR-PROJECT-005-001`) | **Not a screen** | It emits; FA Calendar renders. Adding a PM calendar tab would duplicate FA Calendar's screen and fork its UI conventions. |
| **Gantt visualization** | n/a — renderer (`FR-PROJECT-005-002`) | **Not a separate tab** | Render inside `projects` / `tasks` behind a view switch that carries the current filter. A `gantt` tab would arrive with no filter context — exactly the failure the CRM adoption had to work around for pager links. |
| **Project templates** | `0_ksf_projectmanagement_project_types` (shared) | **On `project_types`** | Already covered. |
| **CSV import** | — | **Not yet placed** | The inventory marks it ✅ but there is no import tab. **Unresolved**: is it an action on `projects` or a separate screen? Flagged, not decided. |

## Finding 3 — the proposed surface

```
dashboard · projects · tasks · documents · team · reports · project_types
```

**Seven tabs, one sub-view** (Task Dependencies, inside `tasks`), and **two
renderers that are not screens** (Gantt, inside `projects`/`tasks`).

Net change: **+1 tab**, **+1 sub-view**, **+1 renderer**, **+1 unresolved
placement (CSV import)**.

## Finding 4 — security areas are the real blocker to opening PM to extensions

`PmAppShell::registerCoreTabs()` gives **all six tabs the same area**,
`SA_ksf_FA_ProjectManagementVIEW`. That is why the fail-open described in BR-PM-010
is currently low-impact: there is nothing finer to fall back *from*.

But it also means PM cannot offer a meaningful extension surface. A module
contributing a `pm_register_tabs` tab has no area to name that is narrower than
"see all of project management". **Open the extension surface and the security
split has to happen first** (or in the same change).

**Already declared** in `hooks.php:69-80` (`install_access()`), section
`SS_ksf_FA_ProjectManagement` = `134 << 8`:

```php
$security_sections[SS_ksf_FA_ProjectManagement] = _("Project Management");
$security_areas['SA_ksf_FA_ProjectManagementVIEW']   = SS_ksf_FA_ProjectManagement | 1, _("View ");
$security_areas['SA_ksf_FA_ProjectManagementMANAGE'] = SS_ksf_FA_ProjectManagement | 2, _("Manage ");
```

So a read/write split already exists — PM simply does not **use** it: all six
registered tabs name `VIEW`. The gap is therefore smaller than it looks. Required
to open the extension surface, mirroring HRM:

| Tab | Area |
|---|---|
| `dashboard`, `projects`, `project_types` | `SA_ksf_FA_ProjectManagementVIEW` (as today) |
| `tasks`, `team`, mutation-heavy tabs | `SA_ksf_FA_ProjectManagementMANAGE` — **already declared, currently unused** |
| `documents` | new `SA_FA_PM_DOCUMENTS` (needs adding) |
| `reports` | new `SA_FA_PM_REPORTS` (read-only), or keep `VIEW` |

A contributed third-party tab names its **own** area regardless, which is the
whole point; PM's own split only affects what PM's tabs can hand to a
contributor.

Only the **`SA_*` string is a stable contract**; `add_access_extensions()`
reassigns the integer codes at runtime (`AGENTS_ARCH.md` §8). And granting an
area requires granting its **section** in the same transaction, or the area is
dropped at login while the database row looks correct.

## Finding 5 — `createController()` is the extension seam PM has not exposed

`AbstractAppShell::createController()` does `new $class()` with no arguments.
`HrmAppShell` overrides it to `new $class(null, ['view' => $tab->getKey()])` so a
controller can build links that keep its own tab selected. `CrmAppShell` overrides
it only for its Admin hub.

PM uses neither, so a contributed PM tab controller receives **no tab key**. A
controller that needs to build a self-referencing link cannot. **Recommendation:**
adopt the HRM form of the override as part of the front-controller work — it is
three lines and it is what makes a contributed PM tab practical.

## Open questions for the business owner

1. **CSV import — action or screen?** Currently unplaced.
2. **Task dependencies — sub-view or top-level tab?** This document recommends
   sub-view; the call is a business/UX one.
3. **Should `reports` stay on the broad read area?** Aggregates across projects,
   tasks and revenue are company-sensitive; HRM made the same choice deliberately
   for `reports`.
4. **Does `documents` belong to PM at all**, given BR-PROJECT-004 says
   "FA-native reuse" and a `ksf_Documents` module already exists? `0_ksf_projectmanagement_files`
   exists, so PM owns the row, but ownership of the *screen* is worth confirming
   before a tab is built on it.