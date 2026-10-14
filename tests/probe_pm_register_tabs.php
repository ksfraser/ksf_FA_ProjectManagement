<?php
/**
 * pm_register_tabs proof, run INSIDE the container (PHP 7.4.33).
 *
 * Stands in for a third-party module: defines hook_invoke_all() + _() exactly as
 * a responder would rely on them, registers a tab through pm_register_tabs, then
 * drives the real PmFrontController to prove the contributed tab is reachable
 * AND is checked against its OWN security area rather than the fallback.
 */

$path_to_root = __DIR__ . '/../../..';
require_once __DIR__ . '/bootstrap.php';

$_SESSION = [];

if (!function_exists('_')) {
    function _(string $m)
    {
        return $m;
    }
}

const PROBE_AREA = 'SA_THIRDPARTY_PROBE_VIEW';
const FALLBACK_AREA = 'SA_ksf_FA_ProjectManagementVIEW';

class ProbeController
{
    public function run(): void
    {
        echo "TAB-RENDERED\n";
    }
}

function hook_invoke_all($method, &$data = null, $opts = null)
{
    if ($method !== 'pm_register_tabs') {
        return null;
    }
    // Exactly what a third-party hooks class does.
    $data['tabs'][] = [
        'key'              => 'probe',
        'label'            => 'Probe',
        'security'         => PROBE_AREA,
        'controller_class' => ProbeController::class,
        'priority'         => 99,
        'order'            => 0,
    ];
    return null;
}

$_GET = [];
$shell = new \ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell();
$fc    = new \ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController(
    $shell,
    FALLBACK_AREA,
    $path_to_root
);

echo "register hook    = " . $shell->getRegisterHook() . "\n";
echo "default view     = " . $shell->getDefaultView() . "\n";
echo "core tabs        = " . count($shell->getTabs()) . "\n";

// Pre-boot: a contributed tab cannot exist yet, so it must fall back.
$_GET = ['view' => 'probe'];
echo "pre-boot area    = " . $fc->getSecurityArea() . " (expected " . FALLBACK_AREA . ")\n";
echo "pre-boot tab     = " . $fc->resolveTabKey() . " (expected dashboard)\n";

// run() boots, which fires the hook, then re-resolves.
$checked = [];
$fc2 = new \ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController(
    $shell,
    FALLBACK_AREA,
    $path_to_root,
    [
        'security_checker' => static function (string $area) use (&$checked): void {
            $checked[] = $area;
        },
        'load_ui_includes' => false,
    ]
);
$_GET = ['view' => 'probe'];
$fc2->run();
echo "after boot tabs  = " . count($shell->getTabs()) . "\n";
echo "contributed tab  = " . var_export($shell->getTab('probe') !== null, true) . "\n";
echo "resolved tab     = " . $fc2->getResolvedTab() . "\n";
echo "areas checked    = " . implode(',', $checked) . " (expected " . PROBE_AREA . ")\n";

// The menu must list the contributed tab.
echo "menu has probe   = " . (strpos($shell->renderMenu('probe'), 'probe') !== false ? 'yes' : 'no') . "\n";