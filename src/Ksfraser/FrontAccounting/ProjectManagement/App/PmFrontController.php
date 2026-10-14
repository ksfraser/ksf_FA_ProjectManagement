<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\AbstractFrontController;
use ksfraser\FrontAccounting\Common\App\TabRegistration;

/**
 * PmFrontController — PM's front controller, reduced to a declaration.
 *
 * Everything the old index.php preamble did by hand — resolve the tab from
 * `?view=`, resolve the per-tab security area, boot the shell so
 * `pm_register_tabs` can contribute tabs, render the sub-menu, enforce access,
 * dispatch to the tab controller SRP — now lives in
 * {@see AbstractFrontController} (package `ksfraser/ksf-fa-common`, v1.0.13+).
 * What is left in the subclass is what is genuinely PM-specific:
 *
 *   - the page title ("Project Management");
 *   - the <head> date-picker bundle;
 *   - the action verb, which PM has always left to the tab controller.
 *
 * The four constructor options below are the reason this subclass exists beyond
 * the title and the JavaScript. They pin PM's published URL surface, which must
 * not move:
 *
 *   tab_param      'view' — the parameter PM has always emitted. The sub-menu
 *                   (PmAppShell → FAModuleMenu) mints `?view=`; every tab
 *                   controller is reached through it; and
 *                   AbstractTabController::preserveParams() defaults to
 *                   ['view'], which is what keeps `view` on the pager links.
 *                   `?tab=` is accepted as the house-standard alias but, as
 *                   before, is never emitted.
 *   alias_param    'tab'  — accepted, never emitted.
 *   normalise_get  false  — the base class would otherwise rewrite the request
 *                   into `$_GET['tab']` and UNSET `$_GET['view']`.
 *                   MasterSummaryTable::pagerUrl() copies every name in
 *                   preserve_params verbatim out of $_GET onto the pager links
 *                   it builds, so normalising would silently strip the tab off
 *                   every pager link in the app and drop the reader back to the
 *                   dashboard. Leaving $_GET alone is what makes the base
 *                   class's "the pager parameter is never a tab selector"
 *                   guarantee the ONLY new routing rule PM gains.
 *   load_ui_includes false — index.php includes includes/ui.inc at GLOBAL scope
 *                   (it reads $path_to_root as a global in its including scope,
 *                   so it cannot move into run()). Same proven path HRM and
 *                   CRM use.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.1.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (host page = thin router)
 * @BABOK Related: BR-012 (Project Management app shell)
 */
class PmFrontController extends AbstractFrontController
{
    /**
     * PM's controller options, overridable per instance.
     *
     * @return array<string, mixed>
     *
     * @since 1.1.0
     */
    public static function defaultOptions(): array
    {
        return [
            'tab_param'        => 'view',
            'alias_param'      => 'tab',
            'normalise_get'    => false,
            'load_ui_includes' => false,
        ];
    }

    /**
     * @param AbstractAppShell     $appShell         PM application shell
     * @param string                $fallbackSecurity Security area for a tab that
     *                                               declares none; never empty
     * @param string                $pathToRoot       Path to the FA root
     * @param array<string, mixed> $options          Overrides for the PM defaults
     *
     * @throws \ksfraser\FrontAccounting\Common\App\FrontControllerSecurityException
     *         When $fallbackSecurity is empty
     *
     * @since 1.1.0
     */
    public function __construct(
        AbstractAppShell $appShell,
        string $fallbackSecurity,
        string $pathToRoot = '',
        array $options = []
    ) {
        parent::__construct(
            $appShell,
            $fallbackSecurity,
            $pathToRoot,
            $options + self::defaultOptions()
        );
    }

    /**
     * FA page title.
     *
     * @return string
     *
     * @since 1.1.0
     */
    protected function getPageTitle(): string
    {
        return _("Project Management");
    }

    /**
     * Extra <head> JavaScript the resolved tab needs.
     *
     * PM has always shipped the date picker on every tab (the entry forms render
     * date_row() controls), so this is deliberately unconditional on the tab
     * key — narrowing it would be a behaviour change, not a cleanup.
     *
     * @param TabRegistration $tab Resolved tab
     *
     * @return string
     *
     * @since 1.1.0
     */
    protected function getPageJs(TabRegistration $tab): string
    {
        if (!function_exists('user_use_date_picker') || !user_use_date_picker()) {
            return '';
        }

        return function_exists('get_js_date_picker') ? get_js_date_picker() : '';
    }

    /**
     * Route an action verb.
     *
     * NONE of PM's six tab controllers implement runAction() — verified across
     * DashboardTabController, ProjectsTabController, TasksTabController,
     * TeamTabController, ReportsTabController and ProjectTypesTabController.
     * They own their verbs inside run() → handlePost() (the `edit_<pk>` /
     * `delete_<pk>` row buttons and the `<pk>_submit` footer button), and
     * index.php has always dispatched the tab regardless of `action`. PM also
     * mints no `action=` link anywhere, so in practice this arm is unreachable
     * from the app itself.
     *
     * Without this override the base class would route a verb to a controller
     * method that does not exist and render "This action is not available for
     * the selected tab" INSTEAD of the tab — i.e. a hand-crafted
     * `?view=projects&action=save` would silently stop being a projects page.
     * Dispatching the tab unconditionally reproduces today's behaviour exactly.
     *
     * Safe to override in the way the base class documents: access has already
     * been enforced by run() before this is reached.
     *
     * @param TabRegistration $tab    Resolved tab
     * @param string          $action Action verb
     *
     * @return void
     *
     * @since 1.1.0
     */
    protected function dispatchAction(TabRegistration $tab, string $action): void
    {
        $this->getAppShell()->dispatch($tab->getKey());
    }
}