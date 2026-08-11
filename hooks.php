<?php
/**
 * KSF FrontAccounting Module Hooks
 * 
 * STANDARD PATTERNS:
 * 
 * 1. ADDING MODULE TABS
 *    Define a class extending 'application' in hooks.php.
 *    Return new instance from install_tabs().
 *    Include add_extensions() to load other modules' install_options.
 * 
 * 2. ADDING MENU ITEMS TO EXISTING APPS
 *    Use install_options() with switch($app->id).
 *    Use add_module() + add_lapp_function() for new menu section.
 * 
 * 3. DATABASE SCHEMA
 *    DO NOT create tables in PHP code.
 *    Use sql/install.sql with @TB_PREF@ placeholders.
 *    Call $this->update_databases() in activate_extension().
 * 
 * 4. SECURITY
 *    Define SS_<MODULE> constant (section << 8).
 *    Define SA_<MODULE>VIEW and SA_<MODULE>MANAGE in install_access().
 * 
 * @package KsfFA_ksf_FA_ProjectManagement
 * @version 2.4.3
 */

define('SS_ksf_FA_ProjectManagement', 134 << 8);
define('KSF_PM_MODULE_NAME', 'ksf_FA_ProjectManagement');
define('KSF_PM_CAPABILITIES', 'project_crud,task_crud,team,sales_order_link,revenue');

class hooks_ksf_FA_ProjectManagement extends hooks {
    var $module_name = 'ksf_FA_ProjectManagement';
    var $version = '1.0.0';

    /**
     * Add module tab
     * 
     * Return new application class instance to add a tab.
     * Omit or return nothing to skip tab addition.
     * 
     * @param application|null $app Ignored
     * @return application|null New tab application instance or nothing
     */
    function install_tabs($app) {
        // Override in modules that add apps
        // return new ksf_FA_ProjectManagement_app();
    }

    /**
     * Add menu items to existing FA applications
     * 
     * @param application $app FA application instance
     */
    function install_options($app) {
        // Override in modules that add menu items
    }

    /**
     * Define security areas
     * 
     * @return array [0] => $security_areas, [1] => $security_sections
     */
    function install_access() {
        $security_sections[SS_ksf_FA_ProjectManagement] = _("");
        $security_areas['SA_ksf_FA_ProjectManagementVIEW'] = array(
            SS_ksf_FA_ProjectManagement | 1, 
            _("View ")
        );
        $security_areas['SA_ksf_FA_ProjectManagementMANAGE'] = array(
            SS_ksf_FA_ProjectManagement | 2, 
            _("Manage ")
        );
        return array($security_areas, $security_sections);
    }

    /**
     * Activate extension
     * 
     * @param int $company Company number
     * @param bool $check_only Only check if activation possible
     * @return bool Success
     */
    function activate_extension($company, $check_only=true) {
        $this->ensure_composer_dependencies();
        
        // Apply sql/install.sql using update_databases()
        // This handles @TB_PREF@ replacement automatically
        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates = array('install.sql' => array($this->module_name));
            return $this->update_databases($company, $updates, $check_only);
        }
        
        return true;
    }

    /**
     * Install composer dependencies if needed
     */
    private function ensure_composer_dependencies() {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (file_exists($autoload_path)) {
            return;
        }
        
        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }
        
        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('KSF Module: composer install failed: ' . implode("\n", $output));
        }
    }

    /**
     * Return module constants for inter-module capability discovery.
     * 
     * @param array $data Result bucket (by reference)
     * @param array|null $opts Options
     * @return array Module constants
     */
    function getModuleConstants(&$data, $opts = null) {
        $constants = array(
            'KSF_PM_MODULE_NAME' => KSF_PM_MODULE_NAME,
            'KSF_PM_CAPABILITIES' => KSF_PM_CAPABILITIES,
        );
        $data['constants'] = $constants;
        return $constants;
    }

    /**
     * Return module capabilities with descriptions.
     * 
     * @param array $data Result bucket (by reference)
     * @param array|null $opts Options
     * @return array Capabilities keyed by capability name
     */
    function getModuleCapabilities(&$data, $opts = null) {
        $capabilities = array(
            'project_crud' => array(
                'description' => 'Create and manage projects',
                'methods' => array(),
                'events' => array(),
            ),
            'task_crud' => array(
                'description' => 'Create and manage tasks with hierarchies',
                'methods' => array(),
                'events' => array(),
            ),
            'team' => array(
                'description' => 'Assign employees to project teams',
                'methods' => array(),
                'events' => array(),
            ),
            'sales_order_link' => array(
                'description' => 'Link imported FA orders to projects',
                'methods' => array('linkOrderToProject', 'onOrderImported'),
                'events' => array('ORDER_IMPORTED'),
            ),
            'revenue' => array(
                'description' => 'Project revenue recognition from linked orders',
                'methods' => array('listRevenueByProject', 'getRevenueSummary'),
                'events' => array('ORDER_IMPORTED'),
            ),
        );
        $data['capabilities'] = $capabilities;
        return $capabilities;
    }

    /**
     * Check whether the module provides a capability.
     * 
     * @param array $data Result bucket (by reference)
     * @param array|null $opts Options (capability)
     * @return bool Capability availability
     */
    function hasCapability(&$data, $opts = null) {
        $capability = isset($opts['capability']) ? $opts['capability'] : (isset($data['capability']) ? $data['capability'] : null);
        if ($capability === null) {
            $data['has_capability'] = false;
            $data['error'] = 'No capability specified';
            return false;
        }
        $capabilities = explode(',', KSF_PM_CAPABILITIES);
        $hasCapability = in_array($capability, $capabilities);
        $data['has_capability'] = $hasCapability;
        $data['capability_checked'] = $capability;
        return $hasCapability;
    }

    /**
     * Respond to a capability request.
     * 
     * Supports 'capabilities', 'constants', and 'has:<capability>'.
     * 
     * @param array $data Result bucket (by reference)
     * @param array|null $opts Options (request)
     * @return mixed Result of the requested operation or null
     */
    function respondToCapabilityRequest(&$data, $opts = null) {
        $request = isset($opts['request']) ? $opts['request'] : (isset($data['request']) ? $data['request'] : 'capabilities');
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'has:') === 0) {
            $capability = substr($request, 4);
            return $this->hasCapability($data, array('capability' => $capability));
        }

        switch ($request) {
            case 'capabilities':
                return $this->getModuleCapabilities($data, $opts);
            case 'constants':
                return $this->getModuleConstants($data, $opts);
            default:
                $data['error'] = 'Unknown request type: ' . $request;
                return null;
        }
    }

    /**
     * order_imported listener: link orders to projects and record revenue.
     * 
     * Invoked by FA's hook_invoke_all('order_imported', $data) with the
     * payload broadcast by source modules (Square, WooCommerce). The
     * listener is a no-op when the PM source tree is unavailable.
     * 
     * @param array $data Event payload (by reference)
     * @param array|null $opts Options
     * @return void
     */
    function order_imported(&$data, $opts = null) {
        if (!class_exists('ksfraser\FrontAccounting\ProjectManagement\Service\ProjectOrderService')) {
            return;
        }
        $service = new \ksfraser\FrontAccounting\ProjectManagement\Service\ProjectOrderService();
        $links = $service->onOrderImported($data);
        $data['project_links_created'] = count($links);
    }
}
