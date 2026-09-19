<?php
/**
 * ksf_FA_ProjectManagement Entry Point
 *
 * App-shell router: resolves the ?view= tab from the PmAppShell, sets the
 * per-view security BEFORE session.inc, boots the shell (fires the
 * `pm_register_tabs` register-with-me hook so other modules can add tabs),
 * renders the sub-menu and dispatches to the tab controller SRP.
 *
 * The tab controllers and their services/repositories sit on the shared
 * data-dictionary + query-builder package (ksf-common-db): DAOs code against
 * DbConnectionInterface and FaDbAdapter delegates every operation to FA's
 * native db_* calls at runtime. The pre/post lifecycle hooks fire through
 * ksf-fa-common's WorkflowHooksTrait as `pm_*_before_save` etc.
 *
 * PHP 7.3 compatible — no PHP 8+ syntax.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 */

chdir(__DIR__);

if (file_exists(__DIR__ . '/bootstrap.php')) {
    require_once __DIR__ . '/bootstrap.php';
}

$path_to_root = "../..";

$appShell = new \ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell();

$view = isset($_GET['view']) ? (string) $_GET['view'] : $appShell->getDefaultView();
if ($appShell->getTab($view) === null) {
    $view = $appShell->getDefaultView();
}

$page_security = $appShell->getSecurity($view, 'SA_ksf_FA_ProjectManagementVIEW');
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

// FA convention: pages include ui.inc themselves (main.inc only loads
// ui_controls). Required for FA-native UI helpers used by the tab forms.
include_once($path_to_root . "/includes/ui.inc");

// Fire the register-with-me hook: other modules may add their tabs now.
$appShell->boot();

$js = '';
if (function_exists('user_use_date_picker') && user_use_date_picker()) {
    $js .= get_js_date_picker();
}

page(_("Project Management"), false, false, '', $js);

echo $appShell->renderMenu($view);

$appShell->dispatch($view);

end_page();