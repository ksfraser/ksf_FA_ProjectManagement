<?php
/**
 * Project Management Settings
 */

$path_to_root = "../../..";

// Security area is declared by hooks_ksf_FA_ProjectManagement::install_access();
// FA page() enforces it via check_page_security() at render time.
$page_security = 'SA_ksf_FA_ProjectManagementMANAGE';
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();
include_once($path_to_root . "/includes/ui.inc");

page(_("Project Management Settings"), true, false, "", "");

echo '<h3>' . _("Project Management Settings") . '</h3>';
echo '<p>' . _("Settings configuration coming soon.") . '</p>';
echo '<ul>';
echo '<li>Default project statuses</li>';
echo '<li>Default task statuses</li>';
echo '<li>Notification settings</li>';
echo '<li>Integration settings</li>';
echo '</ul>';

page_end();