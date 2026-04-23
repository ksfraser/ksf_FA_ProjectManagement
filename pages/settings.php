<?php
/**
 * Project Management Settings
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

if (!$session->check_access('PM_ADMIN')) {
    display_error("Access denied");
    exit;
}

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