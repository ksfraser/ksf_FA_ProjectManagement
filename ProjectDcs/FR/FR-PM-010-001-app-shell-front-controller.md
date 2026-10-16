# FR-PM-010-001 — PM app-shell front controller

@BABOK Related: BR-PM-010; FR-006-007 (field metadata); AGENTS_ARCH.md §9
          (FA page security) and §8 (security areas / sections).
@UML : APP_TAB_ARCHITECTURE.md §11 (host page = thin router);
        `ksfraser\FrontAccounting\Common\App\AbstractFrontController`.
Status: **Proposed — design only. NOT implemented.** No PM code, test or
       deployment exists for this requirement as of 2026-10-13.
Module: ksf_FA_ProjectManagement (dev tree `~/Documents/ksf_FA_ProjectManagement`).

---

## Need (BABOK What-not-How)

PM already has an app shell (`PmAppShell`, `appId = 'pm'`, hook
`pm_register_tabs`) and six controller-backed tabs. What it does not have is the
non-bypassable front controller, and the specific consequence is a security one:
a tab contributed by another module is served under PM's broad read area rather
than under the area the contributor declared.

## Requirement

1. **`index.php` shall instantiate a `PmFrontController` subclassing
   `AbstractFrontController`** at
   `src/FrontAccounting/ProjectManagement/App/PmFrontController.php`.
2. **Two-phase bootstrap**, unchanged in shape from PM's current `index.php`:

   ```php
   $frontController = new \ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController(
       new \ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell(),
       'SA_ksf_FA_ProjectManagementVIEW',
       $path_to_root,
       ['load_ui_includes' => false]
   );

   // Phase A — global scope. $page_security must be set before session.inc, and
   // ui.inc reads $path_to_root as a global in its including scope.
   $page_security = $frontController->getSecurityArea();
   include_once($path_to_root . "/includes/session.inc");
   add_access_extensions();
   include_once($path_to_root . "/includes/ui.inc");

   // Phase B — boot() + re-resolve, $_GET handling, page + menu, the single
   // access check, dispatch, close.
   $frontController->run();
   ```

   `session.inc` and `includes/ui.inc` both read `$path_to_root` /
   `$page_security` as **globals in their including scope**. From inside a method
   they are method locals and every nested include resolves against the
   filesystem root and fails silently. `AbstractFrontController::loadUiIncludes()`
   now seeds `$path_to_root` itself (package v1.0.14), but the global include at
   the position PM already uses is the proven path and is not being changed.

3. **Four option overrides, mirroring CRM's** (because `PmAppShell`'s view param
   is `'view'`, exactly like `CrmAppShell`'s):

   | Option | Value | Reason |
   |---|---|---|
   | `tab_param` | `'view'` | `PmAppShell` mints `?view=`; `FAModuleMenu` and any controller-built link follow it. |
   | `alias_param` | `'tab'` | House-standard selector accepted so `?tab=` resolves; never emitted. |
   | `normalise_get` | `false` | `AbstractTabController::preserveParams()` defaults to `['view']` and `MasterSummaryTable::pagerUrl()` copies those `$_GET` keys verbatim. Normalising would unset `$_GET['view']` and **every PM pager link would lose its tab**. |
   | `load_ui_includes` | `false` | `index.php` already includes `includes/ui.inc` at global scope (line 43). |

4. **Page title and `<head>` JS** move from `index.php:53` / `index.php:48-51`
   into overridables:

   ```php
   protected function getPageTitle(): string
   {
       return _("Project Management");
   }

   protected function getPageJs(TabRegistration $tab): string
   {
       // Unconditional, as today. Narrowing it per tab would be a behaviour
       // change, not a cleanup.
       if (!function_exists('user_use_date_picker') || !user_use_date_picker()) {
           return '';
       }
       return function_exists('get_js_date_picker') ? get_js_date_picker() : '';
   }
   ```

5. **Failure is closed by construction**, inherited from the base:
   a. `$fallbackSecurity` is required and rejected when empty/whitespace;
   b. `run()` is `final` — PM cannot reorder or omit a step;
   c. `enforceSecurity()` is `final protected`;
   d. enforcement runs once, **before** the action path is chosen;
   e. no `check_page_security()` → the request is refused, not rendered.
6. **A hook-contributed tab is checked against its own area.** `getSecurityArea()`
   runs before `session.inc` and the register hook fires on `boot()`, which needs
   a session, so a contributed tab does not exist when the area is chosen.
   `run()` re-resolves after `boot()` and enforces against the contributed tab's
   own area. **This is the requirement PM cannot satisfy today.**
7. **`dispatchAction()` is reviewed before adopting the base unmodified.**
   PM's six controllers are plain `AbstractTabController` subclasses; whether any
   of them implements `runAction()` is **not yet verified**. If none does, the
   base's default would render "This action is not available for the selected
   tab." on every `?action=` request. CRM hit exactly this and overrode
   `dispatchAction()` to dispatch the tab. **PM must check this first** — it is
   the single highest-risk item in the adoption, because a silent no-op on save
   is invisible until a user loses data.
8. **Canonical URL shape.** `?view=<tab>` reads; `?view=<tab>&page=<n>` reads a
   pager position; `?view=<tab>&action=<verb>` is a mutation. `page` is never a
   tab selector. `?tab=<tab>` is the accepted alias.

## Non-requirements

- **No new tabs are created by this FR.** The `documents` tab and the
  task-dependencies sub-view are separate work items in BR-PM-010's tab-structure
  section.
- **No security-area split.** PM already declares
  `SA_ksf_FA_ProjectManagementVIEW` and `SA_ksf_FA_ProjectManagementMANAGE` in
  `install_access()` (`hooks.php:69-80`); wiring the six tabs onto the `MANAGE`
  area is a separate change. Until then `$fallbackSecurity` and every tab share
  `VIEW` and the fail-open has nothing finer to leak into.
- **No CSRF token.** `resolveAction()` reads `$_REQUEST`; `?action=edit&id=N` as a
  GET pre-load is legitimate. Nothing in the base emits or verifies a token.
- **No record-level authorisation.** The module's own
  `ProjectDcs/Business Requirements.md` (RBAC roles → FULL / TEAM / READ_ONLY,
  defaulting to READ_ONLY when RBAC is absent) is a **different layer** and is
  not addressed here.

## Acceptance

See BR-PM-010 ARI-1 … ARI-11. Specifically, PM-specific:

- ARI-2 → a fake responder contributing `SA_FA_PM_FAKE` is served to a user holding it and refused to a user holding only `SA_ksf_FA_ProjectManagementVIEW`.
- ARI-4 → `?page=2&view=tasks` stays on the tasks tab.
- ARI-6 → every PM POST save still persists. **Verify each of the six tabs
  individually**; a single tab's controllers implementing `runAction()` and the
  other five not is a realistic split and would produce a partial failure.
- ARI-10 → the date picker is present on the same tabs before and after.

## Open items before implementation

1. **Check `runAction()` on all six PM controllers.** Highest risk. Unverified as
   of 2026-10-13.
2. **Confirm `ksfraser/ksf-fa-common` ≥ v1.0.14 in PM's `composer.json` and
   `composer.lock`.** PM's current vendor has no `AbstractFrontController.php`.
3. **Decide `dispatchAction()`**: dispatch the tab (CRM's choice) or implement
   `runAction()` on the controllers (HRM's choice, via
   `TabActionRouting`). HRM's uniform-verb-trait approach is the better long-term
   shape and is the recommendation; CRM's override is the cheaper one.
4. **Decide whether the `documents` tab and the dependencies sub-view ship with
   the adoption or after it.** Recommendation: after, so the adoption diff stays
   reviewable as a single mechanical change.

## Traceability

- BR-PM-010 → ARCH-PM-010-001 (tab surface alignment)
- Reference implementations: `ksf_FA_HRM/src/App/HrmFrontController.php` (77 lines),
  `ksf_FA_CRM/src/FrontAccounting/CRM/App/CrmFrontController.php` (156 lines)
- Shared package: `ksf_FA_Common/src/App/AbstractFrontController.php` (623 lines)