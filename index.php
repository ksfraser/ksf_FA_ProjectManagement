<?php
/**
 * ksf_FA_ProjectManagement — single front controller for the whole application.
 *
 * This file is the whole preamble. Everything it used to do by hand — resolving
 * the tab from `view`, resolving the per-tab security area, booting the shell so
 * `pm_register_tabs` can contribute tabs, rendering the sub-menu, dispatching to
 * the tab controller SRP — now lives in
 * `ksfraser\FrontAccounting\Common\App\AbstractFrontController` (package
 * `ksfraser/ksf-fa-common` v1.0.14), driven by
 * `ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController`. What is
 * left here is the two-phase bootstrap FA forces on a page: phase A needs the
 * include at GLOBAL scope, phase B is the controller's `run()`.
 *
 * URL shape
 * ---------
 *   index.php                                default tab (dashboard)
 *   index.php?view=<tab>                     read a tab (canonical — unchanged)
 *   index.php?tab=<tab>                      the same tab, house-standard alias
 *                                           (NEW: previously ignored)
 *   index.php?view=<tab>&page=3              tab + pager position
 *   index.php?view=<tab>&action=save         POST only; the verb is the tab
 *                                            controller's own (see
 *                                            PmFrontController::dispatchAction)
 *
 * `view` stays the canonical parameter for PM: the in-page sub-menu
 * (PmAppShell → FAModuleMenu) mints `?view=`, and
 * AbstractTabController::preserveParams() defaults to `['view']`, which is what
 * keeps `view` on the pager links. `tab` is accepted as an alias so the house
 * convention resolves too. `page` is PAGINATION and is never read as a tab
 * selector — that guarantee is the only new routing rule PM gains.
 *
 * The app id is `pm`, so boot() fires `hook_invoke_all('pm_register_tabs')`
 * and another module can add its own tab to the PM menu.
 *
 * `session.inc` is deliberately NOT inside run(): it reads `$page_security` and
 * `$path_to_root` as GLOBALS in the including scope, so from inside a method it
 * would see neither. Hence phase A here, phase B below.
 *
 * This file is TWO levels below the FA root, which is why $path_to_root is
 * '../..' — unlike pages/*.php and pm.php's siblings, which are three.
 * `pm.php` and `pages/*.php` are legacy entry points that include session.inc
 * themselves; they are deliberately NOT routed through this controller.
 *
 * PHP 7.3 compatible — no PHP 8+ syntax.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.1.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (host page = thin router)
 * @BABOK Related: FR-PM-010-001 — the FA page entry point for the PM tab surface.
 *   Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */

chdir(__DIR__);

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

$path_to_root = "../..";

// The fallback security area is a REQUIRED constructor argument and is never
// empty: it is what makes access enforcement fail closed for a tab contributed
// by another module that registers without an area of its own. PmFrontController
// supplies PM's own URL options (`view` canonical, `tab` alias, no $_GET
// normalisation, ui.inc already loaded below).
$frontController = new \ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController(
    new \ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell(),
    'SA_ksf_FA_ProjectManagementVIEW',
    $path_to_root
);

// Phase A (global scope): the area must be known BEFORE session.inc, and it can
// only come from the tabs registered at shell-construction time — the
// pm_register_tabs hook fires on boot(), which needs a session. A tab
// contributed by another module therefore opens the session under the fallback
// area, and run() re-checks it against its OWN area once boot() has run.
$page_security = $frontController->getSecurityArea();
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

// FA's bootstrap loads only ui_view/ui_controls/ui_msgs. ui_input.inc and
// ui_lists.inc must be pulled in by the page, or every helper the tab
// controllers use — text_row(), amount_row(), date_row(), array_selector_row(),
// submit_row() — is an undefined function.
//
// This include has to happen HERE, at global scope, and PmFrontController
// therefore turns the base class's own load_ui_includes step off. includes/ui.inc
// reads `$path_to_root` as a global in its including scope; it is included here,
// at global scope, where the value is correct.
include_once($path_to_root . "/includes/ui.inc");

// Phase B: boot() + re-resolve, page and menu render, the single access check,
// then dispatch.
$frontController->run();